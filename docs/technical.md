# Technical overview

## Runtime and structure

The application is a server-rendered PHP 8+ application using MySQL or a compatible recent MariaDB release through PDO. Bootstrap and Bootstrap Icons are loaded from a CDN. Local development is configured for XAMPP in `config/database.php`.

- `index.php` maps query-string routes to controllers.
- `src/Controllers` handles HTTP input and access checks.
- `src/Services` contains reservation, pricing, rental, availability, payment, maintenance, notification, backup, and driver rules.
- `views/` contains public, customer, staff, and role dashboard templates.
- `database/carrental.sql` is the additive schema and seed source; `install.php` applies it with `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE`.

## Data and security rules

Use prepared statements through `Crms\Database`, escape view output with `e()`, and include `Crms\Csrf::field()` on every POST form. `Crms\Rbac` checks permissions; customer routes also check ownership. Reservation creation and edits require a validated PNG signature. A pending reservation can be approved or rejected only by a user with reservation-management permission, with a note and signature; those actions are serialized in a database transaction. HDEV payment requests are eligible only for signed, approved reservations. HDEV settlement is queried by transaction reference on the backend before the financial ledger, invoice, reservation payment status, and persisted notifications are updated. Availability periods use pickup-inclusive/return-exclusive intervals and support a configured turnaround buffer. Server-side pricing is authoritative.

The financial transaction table is an append-only ledger at the service layer. Payments, refunds, deposits, and deductions are represented as separate transaction rows; corrections should be posted as new entries. Backups are SQL exports, not an in-app restore feature.

User administration can update account identity, contact details, role and status; optional password resets are hashed before storage. It protects the last active System Administrator and blocks deactivation or demotion of a linked Driver with upcoming assignments. Manager driver access is permission-based and includes records created by the System Administrator. Inspector role grants are limited to maintenance and vehicle viewing; vehicle list/detail queries are restricted to `maintenance` status. Maintenance completion requires a readiness choice and inspection note, and open service jobs prevent release. A vehicle placed back into service is resynchronized against active rentals and confirmed reservations.

## Integrations and current boundaries

- HDEV is used for MoMo and SMS when configured. Pending MoMo requests do not count as paid until a provider response confirms them.
- In-app notifications are persisted, scoped to their user/customer, marked read through authenticated POST requests, and polled by each role dashboard. There is no outbound email provider configured by this codebase.
- Driver scheduling is internal to the system. Drivers need a linked Driver-role user account to sign in and see their assigned schedule.
- Reports are date-filtered screen summaries. PDF/Excel export is not currently implemented.
- The backup export can be created by an authorized user, but restore should be performed manually into a separate database and verified before recovery use.

## Deployment notes

Set production database secrets outside source control, use HTTPS, disable public access to `install.php` after setup, and protect `storage/backups` from direct web downloads. Configure a real business name, currency/tax policy, phone details, payment provider settings, and backup retention before launch. Rehearse restore from a recent SQL export before relying on it for recovery.
