# Administrator guide

## Initial setup

1. Configure the database connection in `config/database.php` or with the `CRMS_DB_*` environment variables.
2. Open `install.php` once. It applies the additive schema and seed data; it does not drop existing tables. If the database is empty, it creates a starter administrator and sample fleet.
3. Sign in with the credentials shown by the installer, change the starter password immediately, and remove or protect `install.php` from public access.
4. In **Settings**, enter the business name, tax rate, deposit amount, turnaround buffer, mileage/late fees, and HDEV callback/SMS settings as applicable.

## Accounts and permissions

Public registrations create active Customer accounts. To add staff, have each person register, then use **Users & roles** to activate the account and assign the correct role. The seeded roles are System Administrator, Business Manager, Rental Officer, Accountant/Cashier, Vehicle Inspector/Maintenance Staff, Customer, and Driver. Permissions are assigned in `database/carrental.sql`; the System Administrator role receives all seeded permissions. At least one active System Administrator must remain, and an administrator cannot change their own role or status.

To give a driver schedule access, assign the Driver role to an active user, then create a driver record under **Drivers** and link that account. Driver records require a unique licence number. Assign drivers from a pending or confirmed reservation. A driver with an upcoming reservation cannot be deactivated until those assignments are removed or rescheduled.

## Backups and recovery

Open **Backup** and create an SQL export. Store a copy outside the web root and include uploaded vehicle images and configuration in the operational backup plan. The generated SQL contains table drop/recreate statements: restore it only into a deliberately selected, backed-up database. The application does not perform an automatic restore or verify a restore. For recovery, restore to a separate database first, confirm tables and representative records, then point the application at the recovered database.

## Operations

- **Reports** accepts a start and end date. Financial totals use transaction date; fleet bookings and rentals use their pick-up date.
- **Audit log** records important account, reservation, fleet, finance, and administrative actions.
- Configure HTTPS and secure database credentials in production. Restrict the installer and backup directory from public downloads.
- Enable HDEV SMS/MoMo only after valid credentials and a public HTTPS callback are configured. Test with the provider's sandbox/account before using live payments.
