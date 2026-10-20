<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Database;

final class ReminderService
{
    /** Create one customer notification for each upcoming return or due invoice. */
    public function sendDueReminders(): array
    {
        $notifications = new NotificationService();
        $counts = ['returns' => 0, 'overdue_returns' => 0, 'payments' => 0];

        $upcomingReturns = Database::fetchAll(
            "SELECT rt.rental_id, rt.rental_number, rt.expected_return_datetime,
                    c.customer_id, c.linked_user_id
             FROM rentals rt JOIN customers c ON c.customer_id = rt.customer_id
             WHERE rt.status IN ('active','overdue')
               AND rt.expected_return_datetime >= NOW()
               AND rt.expected_return_datetime <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
               AND NOT EXISTS (
                    SELECT 1 FROM notifications n
                    WHERE n.channel='in_app' AND n.notification_type='rental.return_reminder'
                      AND n.related_entity_type='rental' AND n.related_entity_id=rt.rental_id
               )
             ORDER BY rt.expected_return_datetime, rt.rental_id"
        );
        foreach ($upcomingReturns as $row) {
            $message = 'Rental ' . $row['rental_number'] . ' is due back on ' . $row['expected_return_datetime'] . '. Please contact the rental office if you need to discuss the return.';
            $notifications->notifyUser(
                $row['linked_user_id'] !== null ? (int) $row['linked_user_id'] : null,
                (int) $row['customer_id'],
                'rental.return_reminder',
                'Vehicle return reminder',
                $message,
                'rental',
                (int) $row['rental_id']
            );
            Audit::log('reminder.return_due', 'rental', (string) $row['rental_id'], 'Return reminder created');
            $counts['returns']++;
        }

        $overdueReturns = Database::fetchAll(
            "SELECT rt.rental_id, rt.rental_number, rt.expected_return_datetime,
                    c.customer_id, c.linked_user_id
             FROM rentals rt JOIN customers c ON c.customer_id = rt.customer_id
             WHERE rt.status IN ('active','overdue') AND rt.expected_return_datetime < NOW()
               AND NOT EXISTS (
                    SELECT 1 FROM notifications n
                    WHERE n.channel='in_app' AND n.notification_type='rental.return_overdue'
                      AND n.related_entity_type='rental' AND n.related_entity_id=rt.rental_id
               )
             ORDER BY rt.expected_return_datetime, rt.rental_id"
        );
        foreach ($overdueReturns as $row) {
            $message = 'Rental ' . $row['rental_number'] . ' was due back on ' . $row['expected_return_datetime'] . ' and is now overdue. Please contact the rental office as soon as possible.';
            $notifications->notifyUser(
                $row['linked_user_id'] !== null ? (int) $row['linked_user_id'] : null,
                (int) $row['customer_id'],
                'rental.return_overdue',
                'Overdue vehicle return',
                $message,
                'rental',
                (int) $row['rental_id']
            );
            Audit::log('reminder.return_overdue', 'rental', (string) $row['rental_id'], 'Overdue return reminder created');
            $counts['overdue_returns']++;
        }

        $paymentDue = Database::fetchAll(
            "SELECT i.invoice_id, i.invoice_number, i.due_date, i.currency, b.outstanding_balance,
                    c.customer_id, c.linked_user_id
             FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
             JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
             WHERE i.status IN ('issued','partially_paid','overdue')
               AND i.due_date IS NOT NULL
               AND i.due_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND b.outstanding_balance > 0
               AND NOT EXISTS (
                    SELECT 1 FROM notifications n
                    WHERE n.channel='in_app' AND n.notification_type='invoice.payment_reminder'
                      AND n.related_entity_type='invoice' AND n.related_entity_id=i.invoice_id
               )
             ORDER BY i.due_date, i.invoice_id"
        );
        foreach ($paymentDue as $row) {
            $message = 'Invoice ' . $row['invoice_number'] . ' has an outstanding balance of '
                . \Crms\Money::format($row['outstanding_balance']) . ' ' . $row['currency'] . ' and is due on ' . $row['due_date'] . '.';
            $notifications->notifyUser(
                $row['linked_user_id'] !== null ? (int) $row['linked_user_id'] : null,
                (int) $row['customer_id'],
                'invoice.payment_reminder',
                'Payment reminder',
                $message,
                'invoice',
                (int) $row['invoice_id']
            );
            Audit::log('reminder.payment_due', 'invoice', (string) $row['invoice_id'], 'Payment reminder created');
            $counts['payments']++;
        }

        return $counts;
    }
}
