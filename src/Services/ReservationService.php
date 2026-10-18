<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Settings;
use InvalidArgumentException;
use PDO;

final class ReservationService
{
    private AvailabilityService $availability;
    private PricingService $pricing;

    /** @var array<string,list<string>> */
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled', 'rejected'],
        'confirmed' => ['cancelled', 'no_show'],
        'cancelled' => [],
        'rejected' => [],
        'completed' => [],
        'no_show' => [],
    ];

    public function __construct(?AvailabilityService $availability = null, ?PricingService $pricing = null)
    {
        $this->availability = $availability ?? new AvailabilityService();
        $this->pricing = $pricing ?? new PricingService();
    }

    public function create(array $input, bool $asStaff): array
    {
        $this->availability->assertValidPeriod((string) ($input['pickup_datetime'] ?? ''), (string) ($input['return_datetime'] ?? ''));
        $created = Database::transaction(function (PDO $pdo) use ($input, $asStaff) {
            $customerId = (int) $input['customer_id'];
            $vehicleId = (int) $input['vehicle_id'];
            $pickup = $input['pickup_datetime'];
            $returnAt = $input['return_datetime'];
            if ($pickup <= (string) $pdo->query('SELECT NOW()')->fetchColumn()) {
                throw new InvalidArgumentException('Pickup must be scheduled in the future.');
            }
            $isRentalOfficer = (Auth::user()['role_name'] ?? '') === 'Rental Officer';
            $discount = $asStaff && !$isRentalOfficer ? (string) ($input['discount_amount'] ?? '0') : '0';

            $customer = Database::fetch('SELECT * FROM customers WHERE customer_id = ? FOR UPDATE', [$customerId]);
            if (!$customer) {
                throw new InvalidArgumentException('Customer not found.');
            }
            if ($customer['status'] === 'blacklisted') {
                throw new InvalidArgumentException('This customer is not allowed to book.');
            }
            if (!empty($customer['license_expiry_date']) && $customer['license_expiry_date'] < date('Y-m-d')) {
                throw new InvalidArgumentException('Driving licence has expired.');
            }

            $vehicle = $this->availability->lockVehicle($pdo, $vehicleId);
            $block = $this->availability->isOperationallyBookable($vehicle);
            if ($block) {
                throw new InvalidArgumentException($block);
            }
            $conflict = $this->availability->hasConflict($vehicleId, $pickup, $returnAt);
            if ($conflict) {
                throw new InvalidArgumentException($conflict);
            }

            $quote = $this->pricing->quoteRates($vehicle, $pickup, $returnAt, (string) $discount);
            $code = $this->nextCode('RSV');
            $status = 'pending';
            $needConfirm = Settings::get('reservation_confirmation_required', 'true') === 'true';
            $confirmedBy = null;
            $confirmedAt = null;
            if ($asStaff && !$needConfirm) {
                $status = 'confirmed';
                $confirmedBy = Auth::id();
                $confirmedAt = date('Y-m-d H:i:s');
            }

            Database::execute(
                'INSERT INTO reservations (
                    reservation_code, customer_id, vehicle_id, pickup_datetime, return_datetime,
                    pickup_location, return_location, agreed_daily_rate, estimated_days, estimated_amount,
                    discount_amount, tax_amount, security_deposit, status, customer_notes, internal_notes, created_by,
                    confirmed_by, confirmed_at
                 ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $code, $customerId, $vehicleId, $pickup, $returnAt,
                    $input['pickup_location'] ?? null, $input['return_location'] ?? null,
                    $quote['agreed_daily_rate'], $quote['estimated_days'], $quote['estimated_amount'],
                    $quote['discount_amount'], $quote['tax_amount'], $quote['security_deposit'],
                    $status, $input['customer_notes'] ?? null, $input['internal_notes'] ?? null, Auth::id(),
                    $confirmedBy, $confirmedAt,
                ]
            );
            $id = (int) Database::lastId();
            (new InvoiceService())->createFromReservation($id);
            if ($status === 'confirmed') {
                (new InvoiceService())->issueBookingPrepayment($id);
                $this->syncVehicleStatus($vehicleId);
            }
            $bookingInvoice = Database::fetch(
                "SELECT invoice_number, total_amount FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%' ORDER BY invoice_id DESC LIMIT 1",
                [$id]
            );
            $customerMessage = 'Reservation ' . $code . ' is ' . $status . '. Pickup ' . $pickup . '.';
            if ($status === 'confirmed' && !empty($bookingInvoice['invoice_number']) && (float) $bookingInvoice['total_amount'] > 0) {
                $customerMessage .= ' Invoice ' . $bookingInvoice['invoice_number'] . ' is ready; pay it before the rental agreement and pickup.';
            } elseif ($status === 'pending') {
                $customerMessage .= ' The booking invoice will be issued after staff confirms the reservation.';
            }
            Audit::log('reservation.created', 'reservation', (string) $id, $code . ' ' . $status);
            $notifications = new NotificationService();
            $notifications->notifyUser(
                Auth::id(),
                $customerId,
                'reservation_created',
                'Reservation ' . $code,
                $customerMessage,
                'reservation',
                $id
            );
            $notifications->notifyRentalOfficers(
                'reservation_created',
                'Reservation ' . $code,
                'A new reservation was created. ' . $customerMessage,
                'reservation',
                $id,
                Auth::id()
            );
            return Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$id]);
        });
        $smsMessage = 'Reservation ' . $created['reservation_code'] . ' is ' . $created['status'] . '. Pickup ' . $created['pickup_datetime'] . '.';
        if ($created['status'] === 'pending') {
            $smsMessage .= ' The booking invoice will be issued after staff confirmation.';
        } elseif ($created['status'] === 'confirmed') {
            $bookingInvoice = Database::fetch(
                "SELECT invoice_number, total_amount FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%' ORDER BY invoice_id DESC LIMIT 1",
                [(int) $created['reservation_id']]
            );
            if (!empty($bookingInvoice['invoice_number']) && (float) $bookingInvoice['total_amount'] > 0) {
                $smsMessage .= ' Pay booking invoice ' . $bookingInvoice['invoice_number'] . ' before pickup.';
            }
        }
        (new SmsService())->sendToCustomer(
            (int) $created['customer_id'],
            'reservation_created',
            'Reservation ' . $created['reservation_code'],
            $smsMessage,
            'reservation',
            (int) $created['reservation_id']
        );
        return $created;
    }

    /** Update a pending booking, or a future confirmed staff booking with no rental agreement. */
    public function update(int $id, array $input, bool $asStaff): array
    {
        $this->availability->assertValidPeriod((string) ($input['pickup_datetime'] ?? ''), (string) ($input['return_datetime'] ?? ''));
        $updated = Database::transaction(function (PDO $pdo) use ($id, $input, $asStaff) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $reservation = $stmt->fetch();
            if (!$reservation) {
                throw new InvalidArgumentException('Reservation not found.');
            }
            if ((string) $input['pickup_datetime'] <= (string) $pdo->query('SELECT NOW()')->fetchColumn()) {
                throw new InvalidArgumentException('Pickup must be scheduled in the future.');
            }
            $editable = $reservation['status'] === 'pending';
            if (!$editable && $asStaff && $reservation['status'] === 'confirmed') {
                $now = (string) $pdo->query('SELECT NOW()')->fetchColumn();
                $rentalStmt = $pdo->prepare('SELECT rental_id FROM rentals WHERE reservation_id = ?');
                $rentalStmt->execute([$id]);
                $hasRental = (bool) $rentalStmt->fetchColumn();
                $editable = $reservation['pickup_datetime'] > $now && !$hasRental;
            }
            if (!$editable) {
                throw new InvalidArgumentException('Only pending reservations, or future confirmed staff bookings without a rental, can be edited.');
            }

            $customerId = (int) $reservation['customer_id'];
            $vehicleId = (int) ($input['vehicle_id'] ?? 0);
            $customer = Database::fetch('SELECT * FROM customers WHERE customer_id = ? FOR UPDATE', [$customerId]);
            if (!$customer || $customer['status'] === 'blacklisted') {
                throw new InvalidArgumentException('This customer is not allowed to book.');
            }
            if (!empty($customer['license_expiry_date']) && $customer['license_expiry_date'] < date('Y-m-d')) {
                throw new InvalidArgumentException('Driving licence has expired.');
            }

            $vehicleIds = array_values(array_unique([(int) $reservation['vehicle_id'], $vehicleId]));
            sort($vehicleIds, SORT_NUMERIC);
            $locked = [];
            foreach ($vehicleIds as $lockedVehicleId) {
                $locked[$lockedVehicleId] = $this->availability->lockVehicle($pdo, $lockedVehicleId);
            }
            $vehicle = $locked[$vehicleId];
            $block = $this->availability->isOperationallyBookable($vehicle);
            if ($block) {
                throw new InvalidArgumentException($block);
            }
            $pickup = (string) $input['pickup_datetime'];
            $returnAt = (string) $input['return_datetime'];
            $conflict = $this->availability->hasConflict($vehicleId, $pickup, $returnAt, $id);
            if ($conflict) {
                throw new InvalidArgumentException($conflict);
            }
            $isRentalOfficer = (Auth::user()['role_name'] ?? '') === 'Rental Officer';
            $discount = $asStaff && !$isRentalOfficer
                ? (string) ($input['discount_amount'] ?? '0')
                : ($isRentalOfficer ? (string) $reservation['discount_amount'] : '0');
            $quote = $this->pricing->quoteRates($vehicle, $pickup, $returnAt, $discount);
            (new InvoiceService())->replaceUnpaidBookingInvoice($id, 'Reservation updated before payment.');
            Database::execute(
                'UPDATE reservations SET vehicle_id = ?, pickup_datetime = ?, return_datetime = ?,
                    pickup_location = ?, return_location = ?, agreed_daily_rate = ?, estimated_days = ?,
                    estimated_amount = ?, discount_amount = ?, tax_amount = ?, security_deposit = ?,
                    customer_notes = ?, internal_notes = ? WHERE reservation_id = ?',
                [
                    $vehicleId, $pickup, $returnAt, $input['pickup_location'] ?? null, $input['return_location'] ?? null,
                    $quote['agreed_daily_rate'], $quote['estimated_days'], $quote['estimated_amount'],
                    $quote['discount_amount'], $quote['tax_amount'], $quote['security_deposit'],
                    $input['customer_notes'] ?? null, $asStaff ? ($input['internal_notes'] ?? null) : $reservation['internal_notes'], $id,
                ]
            );
            (new InvoiceService())->createFromReservation($id);
            if ($reservation['status'] === 'confirmed') {
                (new InvoiceService())->issueBookingPrepayment($id);
            }
            if ((int) $reservation['vehicle_id'] !== $vehicleId) {
                $this->syncVehicleStatus((int) $reservation['vehicle_id']);
            }
            $this->syncVehicleStatus($vehicleId);
            Audit::log('reservation.updated', 'reservation', (string) $id, $reservation['reservation_code'], $reservation, ['vehicle_id' => $vehicleId, 'pickup_datetime' => $pickup, 'return_datetime' => $returnAt]);
            $notifications = new NotificationService();
            $notifications->notifyUser(
                Auth::id(), $customerId, 'reservation_updated', 'Reservation ' . $reservation['reservation_code'],
                'Reservation details have been updated.', 'reservation', $id
            );
            $notifications->notifyRentalOfficers(
                'reservation_updated',
                'Reservation ' . $reservation['reservation_code'],
                'Reservation ' . $reservation['reservation_code'] . ' was updated.',
                'reservation',
                $id,
                Auth::id()
            );
            $assignedDriver = Database::fetch(
                'SELECT d.user_id FROM driver_assignments da JOIN drivers d ON d.driver_id=da.driver_id WHERE da.reservation_id=?',
                [$id]
            );
            if (!empty($assignedDriver['user_id'])) {
                $notifications->notifyUser(
                    (int)$assignedDriver['user_id'],
                    null,
                    'driver_assignment_updated',
                    'Assigned trip updated: ' . $reservation['reservation_code'],
                    'Your assigned trip was updated. Pickup: ' . $pickup . '. Return: ' . $returnAt . '. Pickup location: ' . ((string)($input['pickup_location'] ?? '') ?: 'Not set') . '. Destination: ' . ((string)($input['return_location'] ?? '') ?: 'Not set') . '. Vehicle: ' . $vehicle['registration_no'] . '.',
                    'reservation',
                    $id
                );
            }
            return Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$id]);
        });

        (new SmsService())->sendToCustomer(
            (int) $updated['customer_id'], 'reservation_updated', 'Reservation ' . $updated['reservation_code'],
            'Reservation ' . $updated['reservation_code'] . ' details were updated. Pickup ' . $updated['pickup_datetime'] . '.',
            'reservation', $id
        );
        return $updated;
    }

    public function transition(int $id, string $to, ?string $reason = null): void
    {
        $reason = trim((string) $reason);
        if (in_array($to, ['cancelled', 'rejected'], true)) {
            $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
            if ($reason === '' || $reasonLength > 500) {
                throw new InvalidArgumentException('Enter a cancellation or rejection reason of up to 500 characters.');
            }
        }
        $before = Database::transaction(function (PDO $pdo) use ($id, $to, $reason) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if (!$res) {
                throw new InvalidArgumentException('Reservation not found.');
            }
            $from = $res['status'];
            if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new InvalidArgumentException("Cannot change reservation from {$from} to {$to}.");
            }
            if ($to === 'cancelled' && $from === 'confirmed') {
                if ($res['pickup_datetime'] <= (string) $pdo->query('SELECT NOW()')->fetchColumn()) {
                    throw new InvalidArgumentException('The pickup time has passed. Record a no-show instead of cancelling this reservation.');
                }
                $rentalStmt = $pdo->prepare('SELECT rental_id FROM rentals WHERE reservation_id = ?');
                $rentalStmt->execute([$id]);
                if ($rentalStmt->fetchColumn()) {
                    throw new InvalidArgumentException('A rental agreement exists. Cancel it from the scheduled rental record before pickup.');
                }
            }
            if ($to === 'no_show') {
                if ($res['pickup_datetime'] > (string) $pdo->query('SELECT NOW()')->fetchColumn()) {
                    throw new InvalidArgumentException('A reservation can be marked no-show only after its pickup time.');
                }
                $rentalStmt = $pdo->prepare('SELECT rental_id FROM rentals WHERE reservation_id = ?');
                $rentalStmt->execute([$id]);
                if ($rentalStmt->fetchColumn()) {
                    throw new InvalidArgumentException('A rental agreement exists. Resolve the scheduled rental from its rental record.');
                }
            }
            if ($to === 'confirmed') {
                if ($res['pickup_datetime'] <= (string) $pdo->query('SELECT NOW()')->fetchColumn()) {
                    throw new InvalidArgumentException('This pickup time has passed. Record a no-show or update the reservation before confirming it.');
                }
                $vehicle = $this->availability->lockVehicle($pdo, (int) $res['vehicle_id']);
                $block = $this->availability->isOperationallyBookable($vehicle);
                if ($block) {
                    throw new InvalidArgumentException($block);
                }
                $conflict = $this->availability->hasConflict((int) $res['vehicle_id'], $res['pickup_datetime'], $res['return_datetime'], (int) $res['reservation_id']);
                if ($conflict) {
                    throw new InvalidArgumentException($conflict);
                }
            }
            $paymentReviewRequired = false;
            if (in_array($to, ['cancelled', 'rejected', 'no_show'], true)) {
                $bookingInvoice = Database::fetch(
                    "SELECT invoice_id FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%' AND status <> 'void' ORDER BY invoice_id DESC LIMIT 1 FOR UPDATE",
                    [$id]
                );
                $pending = Database::fetch(
                    "SELECT COUNT(*) AS count FROM financial_transactions
                     WHERE status = 'pending' AND (
                       (transaction_type = 'deposit_received' AND reservation_id = ?)
                       OR (transaction_type = 'payment' AND invoice_id = ?)
                     )",
                    [$id, $bookingInvoice['invoice_id'] ?? 0]
                );
                if ((int) ($pending['count'] ?? 0) > 0) {
                    throw new InvalidArgumentException('A Mobile Money request is still pending. Check its status before cancelling or closing this reservation.');
                }
                $bookingPaid = Database::fetch(
                    "SELECT COALESCE(SUM(amount),0) AS amount FROM financial_transactions
                     WHERE status = 'completed' AND transaction_type = 'payment' AND invoice_id = ?",
                    [$bookingInvoice['invoice_id'] ?? 0]
                );
                $deposit = (new SecurityDepositService())->reservationSummary($id);
                $bookingPaidAmount = (float) ($bookingPaid['amount'] ?? 0);
                $paymentReviewRequired = $bookingPaidAmount > 0 || $deposit['held'] > 0;
                if ($bookingInvoice && $bookingPaidAmount <= 0) {
                    (new InvoiceService())->voidInvoice((int) $bookingInvoice['invoice_id'], 'Reservation ' . $to . ' before payment.');
                } elseif ($bookingInvoice && $bookingPaidAmount > 0) {
                    Database::execute(
                        'UPDATE invoices SET notes = CONCAT(COALESCE(notes, \'\'), ?) WHERE invoice_id = ?',
                        ["\nReservation {$to}; review prepaid funds under the approved cancellation policy.", $bookingInvoice['invoice_id']]
                    );
                }
            }
            $sql = 'UPDATE reservations SET status = ?';
            $params = [$to];
            if ($to === 'confirmed') {
                $sql .= ', confirmed_by = ?, confirmed_at = NOW()';
                $params[] = Auth::id();
            }
            if ($to === 'cancelled' || $to === 'rejected') {
                $sql .= ', cancelled_at = NOW(), cancellation_reason = ?';
                $params[] = $reason;
            }
            $sql .= ' WHERE reservation_id = ?';
            $params[] = $id;
            Database::execute($sql, $params);
            $issuedBookingInvoice = null;
            if ($to === 'confirmed') {
                $issuedBookingInvoice = (new InvoiceService())->issueBookingPrepayment($id);
            }
            $this->syncVehicleStatus((int) $res['vehicle_id']);
            Audit::log('reservation.status', 'reservation', (string) $id, "{$from} -> {$to}", ['status' => $from], ['status' => $to]);
            $assignedDriver = Database::fetch(
                'SELECT d.user_id FROM driver_assignments da JOIN drivers d ON d.driver_id=da.driver_id WHERE da.reservation_id=?',
                [$id]
            );
            $res['assigned_driver_user_id'] = !empty($assignedDriver['user_id']) ? (int)$assignedDriver['user_id'] : null;
            $res['payment_review_required'] = $paymentReviewRequired;
            $res['booking_invoice_number'] = $issuedBookingInvoice['invoice_number'] ?? null;
            return $res;
        });
        $message = 'Reservation ' . $before['reservation_code'] . ' is now ' . $to . '.';
        if ($to === 'confirmed' && !empty($before['booking_invoice_number'])) {
            $message .= ' Booking invoice ' . $before['booking_invoice_number'] . ' is ready. Pay it before the rental agreement and vehicle pickup.';
        }
        if (in_array($to, ['cancelled', 'rejected'], true) && trim((string) $reason) !== '') {
            $message .= ' Reason: ' . trim((string) $reason);
        }
        $notifications = new NotificationService();
        if (!empty($before['payment_review_required'])) {
            $message .= ' Prepaid funds remain recorded and require refund or fee review under the approved policy; no refund was issued automatically.';
            $notifications->notifyUsersWithPermission(
                'transactions.manage',
                'booking_payment_review',
                'Payment review needed: ' . $before['reservation_code'],
                'Reservation status changed to ' . $to . ' with prepaid funds on the ledger. Review any refund or fee under the approval workflow.',
                'reservation',
                $id
            );
        }
        $notifications->notifyUser(
            Auth::id(),
            (int) $before['customer_id'],
            'reservation_status',
            'Reservation ' . $before['reservation_code'],
            $message,
            'reservation',
            $id
        );
        $notifications->notifyRentalOfficers(
            'reservation_status',
            'Reservation ' . $before['reservation_code'],
            $message,
            'reservation',
            $id,
            Auth::id()
        );
        if (!empty($before['assigned_driver_user_id'])) {
            $removed = in_array($to, ['cancelled', 'rejected', 'no_show'], true);
            $notifications->notifyUser(
                (int)$before['assigned_driver_user_id'],
                null,
                $removed ? 'driver_assignment_removed' : 'driver_assignment_updated',
                $removed ? 'Assigned trip removed: ' . $before['reservation_code'] : 'Assigned trip confirmed: ' . $before['reservation_code'],
                $removed
                    ? 'Reservation ' . $before['reservation_code'] . ' is now ' . $to . ' and has been removed from your schedule.'
                    : 'Reservation ' . $before['reservation_code'] . ' is now confirmed and remains on your schedule.',
                'reservation',
                $id
            );
        }
        (new SmsService())->sendToCustomer(
            (int) $before['customer_id'],
            'reservation_status',
            'Reservation ' . $before['reservation_code'],
            $message,
            'reservation',
            $id
        );
    }

    public function syncVehicleStatus(int $vehicleId): void
    {
        $v = Database::fetch('SELECT status FROM vehicles WHERE vehicle_id = ?', [$vehicleId]);
        if (!$v || in_array($v['status'], ['inactive', 'maintenance'], true)) {
            return;
        }
        $activeRental = Database::fetch(
            "SELECT rental_id FROM rentals WHERE vehicle_id = ? AND status IN ('active','overdue','returned') LIMIT 1",
            [$vehicleId]
        );
        if ($activeRental) {
            Database::execute("UPDATE vehicles SET status = 'rented' WHERE vehicle_id = ?", [$vehicleId]);
            return;
        }
        $confirmed = Database::fetch(
            "SELECT reservation_id FROM reservations WHERE vehicle_id = ? AND status = 'confirmed'
             AND pickup_datetime <= NOW() AND return_datetime > NOW() LIMIT 1",
            [$vehicleId]
        );
        if ($confirmed) {
            Database::execute("UPDATE vehicles SET status = 'reserved' WHERE vehicle_id = ?", [$vehicleId]);
            return;
        }
        $future = Database::fetch(
            "SELECT reservation_id FROM reservations WHERE vehicle_id = ? AND status = 'confirmed' AND return_datetime > NOW() LIMIT 1",
            [$vehicleId]
        );
        Database::execute(
            'UPDATE vehicles SET status = ? WHERE vehicle_id = ?',
            [$future ? 'reserved' : 'available', $vehicleId]
        );
    }

    public function nextCode(string $prefix): string
    {
        $n = strtoupper(bin2hex(random_bytes(3)));
        return sprintf('%s-%s-%s', $prefix, date('Ymd'), $n);
    }
}
