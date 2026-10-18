# Administrator guide

## Initial setup

1. Configure the database connection in `config/database.php` or with the `CRMS_DB_*` environment variables.
2. Open `install.php` once. It applies the additive schema and seed data; it does not drop existing tables. If the database is empty, it creates a starter administrator and sample fleet.
3. Sign in with the credentials shown by the installer, change the starter password immediately, and remove or protect `install.php` from public access.
4. In **Settings**, enter the business name, tax rate, deposit amount, turnaround buffer, mileage/late fees, and HDEV callback/SMS settings as applicable.

## Accounts and permissions

Public registrations create active Customer accounts. Create staff accounts or update existing accounts under **Users & roles**; choosing a role assigns its permissions. The seeded roles are System Administrator, Business Manager, Rental Officer, Accountant/Cashier, Vehicle Inspector/Maintenance Staff, Customer, and Driver. Permissions are assigned in `database/carrental.sql`. The System Administrator can manage accounts, roles, settings, backups, audit review, and reports; operational records are read-only, including the payment ledger. Posting payments or refunds remains with the finance role. Re-run `install.php` after applying this update to refresh the administrator's existing permission grants. At least one active System Administrator must remain, and an administrator cannot change their own role or status.

Business Managers and Rental Officers can review pending reservation approvals from their dashboards and approve, reject, or cancel reservations through the reservation status controls. Business Managers can view and manage driver records regardless of whether they were created by a System Administrator or another authorized staff member.

To give a driver schedule access, assign the Driver role to an active user, then create a driver record under **Drivers** and link that account. Driver records require a unique licence number. Assign drivers from a pending or confirmed reservation. A driver with an upcoming reservation cannot be deactivated until those assignments are removed or rescheduled.

## Backups and recovery

Open **Backup** and create an SQL export. Store a copy outside the web root and include uploaded vehicle images and configuration in the operational backup plan. The generated SQL contains table drop/recreate statements: restore it only into a deliberately selected, backed-up database. The application does not perform an automatic restore or verify a restore. For recovery, restore to a separate database first, confirm tables and representative records, then point the application at the recovered database.

## Operations

- **Reports** accepts a start and end date. Financial totals use transaction date; fleet bookings and rentals use their pick-up date.
- **Audit log** records important account, reservation, fleet, finance, and administrative actions.
- Configure HTTPS and secure database credentials in production. Restrict the installer and backup directory from public downloads.
- Enable HDEV SMS/MoMo only after valid credentials and a public HTTPS callback are configured. Test with the provider's sandbox/account before using live payments.
