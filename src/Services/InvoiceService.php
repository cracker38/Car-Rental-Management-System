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
    public function createFromReservation(int $reservationId): array
    {
        return Database::transaction(function (PDO $pdo) use ($reservationId) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
            $stmt->execute([$reservationId]);
            $reservation = $stmt->fetch();
            if (!$reservation) {
                throw new InvalidArgumentException('Reservation not found.');
            }
            $existing = Database::fetch(
                "SELECT * FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%' AND status <> 'void' ORDER BY invoice_id DESC LIMIT 1",
                [$reservationId]
            );
            if ($existing) {
                return $existing;
            }

            $number = (new ReservationService())->nextCode('INV');
            $notes = 'Booking prepayment: estimated vehicle hire. Approved additional charges, if any, are invoiced after return inspection.';
            Database::execute(
                'INSERT INTO invoices (invoice_number, customer_id, reservation_id, issued_by, issue_date, due_date, currency, status, notes)
                 VALUES (?,?,?,?,CURDATE(),NULL,\'RWF\',\'draft\',?)',
                [$number, $reservation['customer_id'], $reservationId, Auth::id(), $notes]
            );
            $invoiceId = (int) Database::lastId();
            $this->addItemUnlocked($invoiceId, 'rental', 'Estimated vehicle hire — booking prepayment', 1, (float) $reservation['estimated_amount']);
            if ((float) $reservation['discount_amount'] > 0) {
                $this->addItemUnlocked($invoiceId, 'discount', 'Booking discount', 1, (float) $reservation['discount_amount']);
            }
            if ((float) $reservation['tax_amount'] > 0) {
                $this->addItemUnlocked($invoiceId, 'tax', 'Tax', 1, (float) $reservation['tax_amount']);
            }
            $this->recalculate($invoiceId);
            Audit::log('invoice.created', 'invoice', (string) $invoiceId, $number . ' booking prepayment quote');
            return Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [$invoiceId]);
        });
    }

    public function issueBookingPrepayment(int $reservationId): ?array
    {
        $invoice = Database::fetch(
            "SELECT * FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%' ORDER BY invoice_id DESC LIMIT 1",
            [$reservationId]
        );
        if (!$invoice || $invoice['status'] === 'void') {
            $invoice = $this->createFromReservation($reservationId);
        }
        if (!$invoice || $invoice['status'] === 'void') {
            return null;
        }
        if ($invoice['status'] === 'draft') {
            Database::execute('UPDATE invoices SET issue_date = CURDATE(), due_date = CURDATE() WHERE invoice_id = ? AND status = \'draft\'', [$invoice['invoice_id']]);
            $this->issue((int) $invoice['invoice_id']);
            $invoice = Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [$invoice['invoice_id']]);
        }
        Audit::log('invoice.booking_issued', 'invoice', (string) $invoice['invoice_id'], $invoice['invoice_number']);
        return $invoice;
    }

    public function assertBookingPaid(int $reservationId): void
    {
        $invoice = Database::fetch(
            "SELECT i.invoice_id, i.status, COALESCE(b.outstanding_balance, i.total_amount) AS outstanding_balance
             FROM invoices i LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
             WHERE i.reservation_id = ? AND i.notes LIKE 'Booking prepayment:%'
             ORDER BY i.invoice_id DESC LIMIT 1",
            [$reservationId]
        );
        if (!$invoice) {
            return; // Keep reservations created before booking prepayment was introduced compatible.
        }
        if ($invoice['status'] === 'void' || $invoice['status'] !== 'paid' || (float) $invoice['outstanding_balance'] > 0.001) {
            throw new InvalidArgumentException('The booking invoice must be fully paid before a rental agreement can be issued or the vehicle can be picked up.');
        }
    }

    public function replaceUnpaidBookingInvoice(int $reservationId, string $reason): void
    {
        $invoice = Database::fetch(
            "SELECT invoice_id FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%' AND status <> 'void' ORDER BY invoice_id DESC LIMIT 1 FOR UPDATE",
            [$reservationId]
        );
        if (!$invoice) {
            return;
        }
        $activity = Database::fetch(
            "SELECT COUNT(*) AS count FROM financial_transactions
             WHERE invoice_id = ? AND status IN ('pending','completed','awaiting_approval')",
            [$invoice['invoice_id']]
        );
        if ((int) ($activity['count'] ?? 0) > 0) {
            throw new InvalidArgumentException('This reservation has payment activity. Contact the rental office before changing a paid or pending booking invoice.');
        }
        $this->voidInvoice((int) $invoice['invoice_id'], $reason);
    }

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
            $bookingInvoice = Database::fetch(
                "SELECT invoice_id, invoice_number FROM invoices WHERE rental_id = ? AND notes LIKE 'Booking prepayment:%' AND status <> 'void' ORDER BY invoice_id DESC LIMIT 1",
                [$rentalId]
            );
            if (!$bookingInvoice) {
                $existing = Database::fetch("SELECT invoice_id FROM invoices WHERE rental_id = ? AND status <> 'void'", [$rentalId]);
                if ($existing) {
                    throw new InvalidArgumentException('An invoice already exists for this rental.');
                }
            } else {
                $existingSupplement = Database::fetch(
                    "SELECT invoice_id FROM invoices WHERE rental_id = ? AND notes LIKE 'Additional rental charges:%' AND status <> 'void' LIMIT 1",
                    [$rentalId]
                );
                if ($existingSupplement) {
                    throw new InvalidArgumentException('An additional-charge invoice already exists for this rental.');
                }
            }
            $number = (new ReservationService())->nextCode('INV');
            $invoiceNotes = $bookingInvoice
                ? 'Additional rental charges: supplement to booking prepayment invoice ' . $bookingInvoice['invoice_number'] . '.'
                : null;
            Database::execute(
                'INSERT INTO invoices (invoice_number, customer_id, reservation_id, rental_id, issued_by, issue_date, due_date, currency, status, notes)
                 VALUES (?,?,?,?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 7 DAY),\'RWF\',\'draft\',?)',
                [$number, $rental['customer_id'], $rental['reservation_id'], $rentalId, Auth::id(), $invoiceNotes]
            );
            $invoiceId = (int) Database::lastId();
            $items = $bookingInvoice ? [] : [['rental', 'Vehicle hire', 1, $rental['base_amount']]];
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
            if (!$bookingInvoice && (float) $rental['discount_amount'] > 0) {
                $items[] = ['discount', 'Discount', 1, $rental['discount_amount']];
            }
            if (!$bookingInvoice && (float) $rental['tax_amount'] > 0) {
                $items[] = ['tax', 'Tax', 1, $rental['tax_amount']];
            }
            if ($bookingInvoice && !$items) {
                throw new InvalidArgumentException('No additional charges were recorded. The booking invoice already covers the rental amount.');
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
            $this->refreshPaymentStatus($invoiceId);
            Audit::log('invoice.issued', 'invoice', (string) $invoiceId, $inv['invoice_number']);
        });
    }

    public function voidInvoice(int $invoiceId, string $reason): void
    {
        Database::transaction(function (PDO $pdo) use ($invoiceId, $reason) {
            $inv = $this->lockInvoice($pdo, $invoiceId);
            if (in_array($inv['status'], ['paid', 'refunded'], true) && (float) $inv['total_amount'] > 0.001) {
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
