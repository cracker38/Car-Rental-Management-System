# Technical overview

## Runtime and structure

The application is a server-rendered PHP 8+ application using MySQL or a compatible recent MariaDB release through PDO. Bootstrap and Bootstrap Icons are loaded from a CDN. Local development is configured for XAMPP in `config/database.php`.

- `index.php` maps query-string routes to controllers.
- `src/Controllers` handles HTTP input and access checks.
- `src/Services` contains reservation, pricing, rental, availability, payment, maintenance, notification, backup, and driver rules.
- `views/` contains public, customer, staff, and role dashboard templates.
- `database/carrental.sql` is the additive schema and seed source; `install.php` applies it with `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE`.

## Data and security rules

Use prepared statements through `Crms\Database`, escape view output with `e()`, and include `Crms\Csrf::field()` on every POST form. `Crms\Rbac` checks permissions; customer routes also check ownership. Reservation creation and edits require a validated PNG signature. Rental Officers alone receive `reservations.decide`; approval or rejection requires a note and the Rental Officer's signature, and those decisions are serialized in a database transaction. Business Managers have read-only reservation access. PawaPay deposits are eligible only for signed, approved reservations. PawaPay settlement is queried by deposit ID on the backend before the financial ledger, invoice, reservation payment status, and persisted notifications are updated. Availability periods use pickup-inclusive/return-exclusive intervals and support a configured turnaround buffer. Server-side pricing is authoritative.

The financial transaction table is an append-only ledger at the service layer. Payments, refunds, deposits, and deductions are represented as separate transaction rows; corrections should be posted as new entries. Backups are SQL exports, not an in-app restore feature.

User administration can update account identity, contact details, role and status; optional password resets are hashed before storage. It protects the last active System Administrator and blocks deactivation or demotion of a linked Driver with upcoming assignments. Manager driver access is permission-based and includes records created by the System Administrator. Inspector role grants are limited to maintenance and vehicle viewing; vehicle list/detail queries are restricted to `maintenance` status. Maintenance completion requires a readiness choice and inspection note, and open service jobs prevent release. A vehicle placed back into service is resynchronized against active rentals and confirmed reservations.

## Integrations and current boundaries

- PawaPay handles new Rwanda Mobile Money deposits; HDEV remains available for SMS and legacy HDEV payment records. Pending requests do not count as paid until the matching provider status is confirmed server-side.
- In-app notifications are persisted, scoped to their user/customer, marked read through authenticated POST requests, and polled by each role dashboard. Notification events also send plain-text email through Gmail SMTP when `config/mail.local.php` or the `CRMS_MAIL_*` environment variables are configured; sent and failed email attempts are recorded separately from in-app notifications. `scripts/reminders.php` can be scheduled to create one-time due-return, overdue-return, and payment-due reminders.
- Driver scheduling is internal to the system. Drivers need a linked Driver-role user account to sign in and see their assigned schedule.
- Reports are date-filtered summaries with CSV export and browser print/save-to-PDF.
- An authorized user can create a SQL export, or an operator can schedule `scripts/backup.php` through the host scheduler. The export is written atomically, concurrent exports are blocked, and the scheduled job applies configurable retention (30 days by default). Restore remains a manual operation into a separate database and must be verified before recovery use.

## Deployment notes

Set production database and mail secrets outside source control and use HTTPS. A successful installer run creates `storage/install.lock` to block later web access. Apache access to `storage/` is denied by `storage/.htaccess`. Configure a real business name, currency/tax policy, phone details, payment provider settings, and backup retention before launch. Schedule recurring backups, copy them outside the host, and rehearse restore from a recent SQL export before relying on it for recovery. SQL exports exclude uploaded vehicle images and local configuration secrets.
