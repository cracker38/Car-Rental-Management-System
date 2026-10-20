# Administrator guide

## Initial setup

1. Configure the database connection in `config/database.php` or with the `CRMS_DB_*` environment variables.
2. Open `install.php` once. It applies the additive schema and seed data; it does not drop existing tables. If the database is empty, it creates a starter administrator and sample fleet. After a successful setup, the installer creates `storage/install.lock` and returns 404 on later visits.
3. Sign in with the credentials shown by the installer and change the starter password immediately. For a planned installer maintenance run, remove `storage/install.lock`, perform the work, and confirm the lock is recreated.
4. In **Settings**, enter the business name, tax rate, deposit amount, turnaround buffer, mileage/late fees, and HDEV SMS settings as applicable. HDEV SMS requires its own API ID and key, configured as `HDEV_SMS_API_ID` and `HDEV_SMS_API_KEY` in the server environment or as `sms_api_id` and `sms_api_key` in the ignored `config/hdev.local.php` file. PawaPay payment credentials do not configure SMS.

## Accounts and permissions

Public registrations create active Customer accounts. Under **Users & roles**, a System Administrator can edit names, email, phone, role and account status, activate or deactivate access, and optionally reset a password. Changing an account's status does not erase its records. At least one active System Administrator must remain, and an administrator cannot change their own role or status. Re-run `install.php` after applying this update to synchronize existing role permissions.

Business Managers and System Administrators can manage driver records and assignments, including records created by the other role. Deactivating a linked Driver account also disables its driver profile; upcoming assignments must be moved first. Inspectors have a maintenance-only fleet view: they can update service work and record whether a vehicle is safe to return to service or must remain on hold. They do not have rental scheduling or driver-management access. A vehicle with an open maintenance job cannot be released, and its readiness decision requires an inspection note.

Rental Officers review, approve, or reject pending reservations. Each decision requires a note and the Rental Officer's signature; the customer's signature and decision are retained with the reservation. Approval issues the booking invoice. Business Managers have read-only access to reservations and their signatures. Business Managers can view and manage driver records regardless of whether they were created by a System Administrator or another authorized staff member.

To give a driver schedule access, assign the Driver role to an active user, then create a driver record under **Drivers** and link that account. Driver records require a unique licence number. Assign drivers from a pending or confirmed reservation. A driver with an upcoming reservation cannot be deactivated until those assignments are removed or rescheduled.

## Backups and recovery

Open **Backup** and create an SQL export, or run the recurring CLI job below. The generated SQL contains table drop/recreate statements: restore it only into a deliberately selected, backed-up database. The application does not perform an automatic restore. Rehearse recovery into a separate database first, confirm tables and representative records, then point the application at the recovered database. SQL exports do not contain uploaded vehicle images or secrets; copy those separately and keep an off-machine backup copy.

### Schedule daily backups on Windows

Create a Windows Task Scheduler task that runs once per day:

- **Program:** `C:\xampp\php\php.exe`
- **Arguments:** `C:\xampp\htdocs\CarrentalMS\scripts\backup.php`
- **Start in:** `C:\xampp\htdocs\CarrentalMS`
- Run it under a Windows account that can access the project backup folder and database.

The job writes complete SQL exports to `storage/backups`, prevents concurrent exports, removes incomplete temporary files after a failed run, and retains 30 days by default. Set the server environment variable `CRMS_BACKUP_RETENTION_DAYS` to a whole number from 1 to 3650 to change retention. Review Task Scheduler's last-run result and copy backups to separate storage. The backup directory denies direct Apache web requests; keep it outside source control as well.

Create a second daily task at the business's preferred reminder time using the same **Program** and **Start in** values, with **Arguments** set to `C:\xampp\htdocs\CarrentalMS\scripts\reminders.php`. It creates one in-app reminder for each rental due within 24 hours, each overdue rental, and each unpaid invoice due today or tomorrow. It also sends email when SMTP is configured. The job keeps a lock so overlapping runs do not create duplicate reminders. Confirm that the system date and time zone are correct before enabling it.

## Operations

- **Reports** accepts a start and end date and supports CSV export and browser print/save-to-PDF. Financial totals use transaction date; fleet bookings and rentals use their pick-up date.
- **Audit log** records important account, reservation, fleet, finance, and administrative actions.
- Configure HTTPS and secure database credentials in production. Restrict the installer and backup directory from public downloads.
- Configure PawaPay with `PAWAPAY_BASE_URL` and `PAWAPAY_API_TOKEN` on the server, or use the ignored `config/pawapay.local.php` for local development. The live endpoint is `https://api.pawapay.io`; payment settlement is verified server-side by deposit ID. HDEV SMS is a separate service and needs its own credentials.
