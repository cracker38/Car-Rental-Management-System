# Rent Car Rwanda — Car Rental Management System

A server-rendered PHP/MySQL car rental system for fleet availability, reservations, rental handover, payments, maintenance, chauffeur assignments, and role-based operations.

## Setup

1. Configure the database in `config/database.php` or set `CRMS_DB_HOST`, `CRMS_DB_PORT`, `CRMS_DB_NAME`, `CRMS_DB_USER`, and `CRMS_DB_PASS`.
2. Open `install.php` once. It applies the additive database schema and seed records. Change the starter administrator password and remove or protect `install.php` after setup.
3. Configure business pricing, tax, deposits, turnaround time, and optional HDEV settings under **Settings**.

See [the administrator guide](docs/admin-guide.md) for setup and recovery details.

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

Reports currently display date-filtered summaries on screen. Email delivery, report exports, and automatic restore are not implemented. HDEV payment and SMS features require provider configuration.
