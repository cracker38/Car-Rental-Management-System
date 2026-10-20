# Rent Car Rwanda — Car Rental Management System

A server-rendered PHP/MySQL car rental system for fleet availability, reservations, rental handover, payments, maintenance, chauffeur assignments, and role-based operations.

## Setup

1. Configure the database in `config/database.php` or set `CRMS_DB_HOST`, `CRMS_DB_PORT`, `CRMS_DB_NAME`, `CRMS_DB_USER`, and `CRMS_DB_PASS`.
2. Open `install.php` once. It applies the additive database schema and seed records, then locks itself with `storage/install.lock`. Change the starter administrator password after setup.
3. Configure business pricing, tax, deposits, turnaround time, and optional HDEV settings under **Settings**.
4. To send notification emails through Gmail, copy `config/mail.local.php.example` to `config/mail.local.php`, then enter the Gmail address and Google app password. The account needs 2-Step Verification for an app password; see [Google's app password instructions](https://support.google.com/accounts/answer/185833). The local file is ignored by Git. Emails are recorded with sent or failed status. Run `C:\xampp\php\php.exe scripts\send_test_email.php recipient@example.com` to send a delivery check.

See [the administrator guide](docs/admin-guide.md) for setup and recovery details.

For recurring backups on Windows, schedule `C:\xampp\php\php.exe` with argument `C:\xampp\htdocs\CarrentalMS\scripts\backup.php` once per day. To send due payment and return reminders, schedule the same PHP executable with `C:\xampp\htdocs\CarrentalMS\scripts\reminders.php`. The backup retention is 30 days by default and can be changed with `CRMS_BACKUP_RETENTION_DAYS`. See the administrator guide for setup details.

## Guides

- [Customer and staff user guide](docs/user-guide.md)
- [Administrator guide](docs/admin-guide.md)
- [Technical overview](docs/technical.md)
- [Staff training guide](docs/training-guide.md)

## Main workflows

- Search live availability by dates, category, vehicle, fuel, transmission, and price.
- Submit signed reservations for staff approval, then pay approved booking invoices through verified HDEV MoMo settlement.
- Create rental agreements and record vehicle pickup, return, inspection, mileage, and fuel.
- Maintain invoices and an append-only payment/deposit/refund ledger with printable receipts.
- Manage maintenance blocks, driver records, chauffeur assignments, notifications, reports, audit logs, and SQL backup exports.
- Export date-filtered reports as CSV or print/save them as PDF from the browser.
- Send scheduled upcoming-return, overdue-return, and payment-due reminders through in-app notifications and configured email.

Reports provide date-filtered summaries, CSV exports, and browser print/save-to-PDF. Scheduled SQL backups are available through `scripts/backup.php`; restore remains a manual, rehearsed operation into a separate database. HDEV payment and SMS features require provider configuration.

## Requirements traceability

See [the SRS compliance and verification report](docs/srs-compliance.md) for the requirement-to-code map, architecture diagrams, project arithmetic checks, and production sign-off actions.
