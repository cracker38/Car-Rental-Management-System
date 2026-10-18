<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Money;
use InvalidArgumentException;
use PDO;

final class InvoiceService
{
    public function createFromRental(int $rentalId): array
    {
        return Database::transaction(function (PDO $pdo) use ($rentalId) {
            $stmt = $pdo->prepare('SELECT * FROM rentals WHERE rental_id = ? FOR UPDATE');
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();
            if (!$rental) {
                throw new InvalidArgumentException('Rental not found.');
            }
            if ($rental['status'] !== 'closed') {
                throw new InvalidArgumentException('Invoices can only be generated from closed rentals after the return inspection is complete.');
            }
            $existing = Database::fetch("SELECT invoice_id FROM invoices WHERE rental_id = ? AND status <> 'void'", [$rentalId]);
            if ($existing) {
                throw new InvalidArgumentException('An invoice already exists for this rental.');
            }
            $number = (new ReservationService())->nextCode('INV');
            Database::execute(
                'INSERT INTO invoices (invoice_number, customer_id, reservation_id, rental_id, issued_by, issue_date, due_date, currency, status)
                 VALUES (?,?,?,?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 7 DAY),\'RWF\',\'draft\')',
                [$number, $rental['customer_id'], $rental['reservation_id'], $rentalId, Auth::id()]
            );
            $invoiceId = (int) Database::lastId();
            $items = [
                ['rental', 'Vehicle hire', 1, $rental['base_amount']],
            ];
            if ((float) $rental['extra_mileage_fee'] > 0) {
                $items[] = ['extra_mileage', 'Extra mileage', 1, $rental['extra_mileage_fee']];
            }
            if ((float) $rental['late_return_fee'] > 0) {
                $items[] = ['late_return', 'Late return', 1, $rental['late_return_fee']];
            }
            if ((float) $rental['damage_fee'] > 0) {
                $items[] = ['damage', 'Damage', 1, $rental['damage_fee']];
            }
            if ((float) $rental['cleaning_fee'] > 0) {
                $items[] = ['cleaning', 'Cleaning', 1, $rental['cleaning_fee']];
            }
            if ((float) $rental['other_charges'] > 0) {
                $items[] = ['other', 'Other charges', 1, $rental['other_charges']];
            }
            if ((float) $rental['discount_amount'] > 0) {
                $items[] = ['discount', 'Discount', 1, $rental['discount_amount']];
            }
            if ((float) $rental['tax_amount'] > 0) {
                $items[] = ['tax', 'Tax', 1, $rental['tax_amount']];
            }
            foreach ($items as [$type, $desc, $qty, $price]) {
                $this->addItemUnlocked($invoiceId, $type, $desc, (float) $qty, (float) $price);
            }
            $this->recalculate($invoiceId);
            Audit::log('invoice.created', 'invoice', (string) $invoiceId, $number);
            return Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [$invoiceId]);
        });
    }

    public function addItem(int $invoiceId, string $type, string $description, float $qty, float $unitPrice): void
    {
        $allowed = ['rental','driver','extra_mileage','late_return','damage','cleaning','fuel','delivery','discount','tax','other'];
        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Invalid invoice item type.');
        }
        Database::transaction(function (PDO $pdo) use ($invoiceId, $type, $description, $qty, $unitPrice) {
            $inv = $this->lockInvoice($pdo, $invoiceId);
            if ($inv['status'] !== 'draft') {
                throw new InvalidArgumentException('Only draft invoices can be edited.');
            }
            $this->addItemUnlocked($invoiceId, $type, $description, $qty, $unitPrice);
            $this->recalculate($invoiceId);
        });
    }

    public function issue(int $invoiceId): void
    {
        Database::transaction(function (PDO $pdo) use ($invoiceId) {
            $inv = $this->lockInvoice($pdo, $invoiceId);
            if ($inv['status'] !== 'draft') {
                throw new InvalidArgumentException('Only draft invoices can be issued.');
            }
            $this->recalculate($invoiceId);
            Database::execute("UPDATE invoices SET status = 'issued' WHERE invoice_id = ?", [$invoiceId]);
            Audit::log('invoice.issued', 'invoice', (string) $invoiceId, $inv['invoice_number']);
        });
    }

    public function voidInvoice(int $invoiceId, string $reason): void
    {
        Database::transaction(function (PDO $pdo) use ($invoiceId, $reason) {
            $inv = $this->lockInvoice($pdo, $invoiceId);
            if (in_array($inv['status'], ['paid', 'refunded'], true)) {
                throw new InvalidArgumentException('Paid invoices cannot be voided; post a refund instead.');
            }
            Database::execute("UPDATE invoices SET status = 'void', notes = CONCAT(COALESCE(notes,''), ?) WHERE invoice_id = ?", ["\nVoid: " . $reason, $invoiceId]);
            Audit::log('invoice.voided', 'invoice', (string) $invoiceId, $reason);
        });
    }

    public function recalculate(int $invoiceId): void
    {
        $items = Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        foreach ($items as $item) {
            $line = (float) Money::mul($item['quantity'], $item['unit_price']);
            Database::execute('UPDATE invoice_items SET line_total = ? WHERE invoice_item_id = ?', [Money::round($line), $item['invoice_item_id']]);
            if ($item['item_type'] === 'discount') {
                $discount += $line;
            } elseif ($item['item_type'] === 'tax') {
                $tax += $line;
            } else {
                $subtotal += $line;
            }
        }
        $total = (float) Money::round($subtotal - $discount + $tax);
        if ($total < 0) {
            throw new InvalidArgumentException('Invoice total cannot be negative.');
        }
        Database::execute(
            'UPDATE invoices SET subtotal = ?, discount_amount = ?, tax_amount = ?, total_amount = ? WHERE invoice_id = ?',
            [Money::round($subtotal), Money::round($discount), Money::round($tax), Money::round($total), $invoiceId]
        );
        $this->refreshPaymentStatus($invoiceId);
    }

    public function refreshPaymentStatus(int $invoiceId): void
    {
        $inv = Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [$invoiceId]);
        if (!$inv || in_array($inv['status'], ['draft', 'void'], true)) {
            return;
        }
        $bal = Database::fetch('SELECT outstanding_balance, net_paid FROM vw_invoice_balances WHERE invoice_id = ?', [$invoiceId]);
        $outstanding = (float) ($bal['outstanding_balance'] ?? $inv['total_amount']);
        $paid = (float) ($bal['net_paid'] ?? 0);
        $status = $inv['status'];
        if ($paid <= 0 && $outstanding > 0) {
            $status = ($inv['due_date'] && $inv['due_date'] < date('Y-m-d')) ? 'overdue' : 'issued';
        } elseif ($outstanding > 0) {
            $status = 'partially_paid';
        } elseif ($paid > (float) $inv['total_amount']) {
            $status = 'refunded';
        } else {
            $status = 'paid';
        }
        Database::execute('UPDATE invoices SET status = ? WHERE invoice_id = ?', [$status, $invoiceId]);
    }

    private function lockInvoice(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Invoice not found.');
        }
        return $row;
    }

    private function addItemUnlocked(int $invoiceId, string $type, string $description, float $qty, float $unitPrice): void
    {
        $line = Money::mul($qty, $unitPrice);
        Database::execute(
            'INSERT INTO invoice_items (invoice_id, item_type, description, quantity, unit_price, line_total) VALUES (?,?,?,?,?,?)',
            [$invoiceId, $type, $description, Money::round($qty), Money::round($unitPrice), $line]
        );
    }
}
