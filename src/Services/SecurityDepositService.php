<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;
use Crms\Money;
use InvalidArgumentException;

final class SecurityDepositService
{
    /** @return array{required:float,held:float,pending:float,due:float,requestable:float} */
    public function reservationSummary(int $reservationId): array
    {
        $reservation = Database::fetch('SELECT security_deposit FROM reservations WHERE reservation_id = ?', [$reservationId]);
        if (!$reservation) {
            throw new InvalidArgumentException('Reservation not found.');
        }

        $totals = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE
                    WHEN transaction_type = 'deposit_received' AND status = 'completed' THEN amount
                    WHEN transaction_type IN ('deposit_deduction','deposit_refund') AND status = 'completed' THEN -amount
                    ELSE 0 END), 0) AS held,
                COALESCE(SUM(CASE
                    WHEN transaction_type = 'deposit_received' AND status = 'pending' THEN amount
                    ELSE 0 END), 0) AS pending
             FROM financial_transactions
             WHERE reservation_id = ? OR rental_id = (SELECT rental_id FROM rentals WHERE reservation_id = ? LIMIT 1)",
            [$reservationId, $reservationId]
        );

        $required = (float) Money::round($reservation['security_deposit']);
        $held = max(0, (float) Money::round($totals['held'] ?? 0));
        $pending = max(0, (float) Money::round($totals['pending'] ?? 0));
        $due = max(0, (float) Money::round($required - $held));

        return [
            'required' => $required,
            'held' => $held,
            'pending' => $pending,
            'due' => $due,
            'requestable' => max(0, (float) Money::round($due - $pending)),
        ];
    }

    public function assertReservationPaid(int $reservationId): void
    {
        if ($this->reservationSummary($reservationId)['due'] > 0.001) {
            throw new InvalidArgumentException(
                'The required security deposit must be confirmed before a rental agreement can be issued or the vehicle can be picked up.'
            );
        }
    }
}
