-- CRMS Professional Reduced Schema
-- MySQL 8.0+ / compatible recent MariaDB
-- IMPORTANT: This creates/uses `carrental`. Back up the existing database first.
-- It does NOT drop or overwrite existing tables. For a fresh install, use a new database.
-- Passwords must be generated with PHP password_hash().
-- Critical booking and financial rules must be enforced in PHP transactions as described below.

CREATE DATABASE IF NOT EXISTS carrental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carrental;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  role_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(60) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  permission_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  permission_key VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_users_role_status (role_id,status),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  customer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  linked_user_id BIGINT UNSIGNED NULL UNIQUE,
  customer_code VARCHAR(30) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  identification_type ENUM('national_id','passport','other') NULL,
  identification_number VARCHAR(100) NULL,
  driving_license_number VARCHAR(100) NULL,
  license_expiry_date DATE NULL,
  status ENUM('active','inactive','blacklisted') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customer_identity (identification_type,identification_number),
  KEY idx_customer_name (last_name,first_name), KEY idx_customer_phone (phone), KEY idx_customer_email (email),
  CONSTRAINT fk_customer_user FOREIGN KEY (linked_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_customer_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS drivers (
  driver_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NULL,
  driving_license_number VARCHAR(100) NOT NULL UNIQUE,
  license_expiry_date DATE NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_driver_status_name (status,last_name,first_name),
  CONSTRAINT fk_driver_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_driver_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_categories (
  category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  default_daily_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  default_weekly_rate DECIMAL(12,2) NULL,
  default_monthly_rate DECIMAL(12,2) NULL,
  passenger_capacity TINYINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_category_rates CHECK (default_daily_rate >= 0 AND (default_weekly_rate IS NULL OR default_weekly_rate >= 0) AND (default_monthly_rate IS NULL OR default_monthly_rate >= 0))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
  vehicle_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  registration_no VARCHAR(30) NOT NULL UNIQUE,
  vin VARCHAR(80) NULL UNIQUE,
  make VARCHAR(80) NOT NULL,
  model VARCHAR(80) NOT NULL,
  manufacture_year SMALLINT UNSIGNED NULL,
  colour VARCHAR(40) NULL,
  transmission ENUM('manual','automatic','other') NOT NULL DEFAULT 'manual',
  fuel_type ENUM('petrol','diesel','hybrid','electric','other') NOT NULL DEFAULT 'petrol',
  daily_rate DECIMAL(12,2) NOT NULL,
  weekly_rate DECIMAL(12,2) NULL,
  monthly_rate DECIMAL(12,2) NULL,
  current_mileage INT UNSIGNED NOT NULL DEFAULT 0,
  fuel_level_percent DECIMAL(5,2) NULL,
  image_path VARCHAR(500) NULL,
  status ENUM('available','reserved','rented','maintenance','inactive') NOT NULL DEFAULT 'available',
  insurance_expiry_date DATE NULL,
  inspection_expiry_date DATE NULL,
  description TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_vehicle_search (category_id,status,daily_rate), KEY idx_vehicle_model (make,model),
  CONSTRAINT fk_vehicle_category FOREIGN KEY (category_id) REFERENCES vehicle_categories(category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_vehicle_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_vehicle_rates CHECK (daily_rate >= 0 AND (weekly_rate IS NULL OR weekly_rate >= 0) AND (monthly_rate IS NULL OR monthly_rate >= 0)),
  CONSTRAINT chk_vehicle_fuel CHECK (fuel_level_percent IS NULL OR fuel_level_percent BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
  reservation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservation_code VARCHAR(40) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  pickup_datetime DATETIME NOT NULL,
  return_datetime DATETIME NOT NULL,
  pickup_location VARCHAR(255) NULL,
  return_location VARCHAR(255) NULL,
  agreed_daily_rate DECIMAL(12,2) NOT NULL,
  estimated_days DECIMAL(8,2) NOT NULL,
  estimated_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  security_deposit DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('pending','confirmed','cancelled','rejected','completed','no_show') NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid','pending','successful','failed','cancelled','refunded') NOT NULL DEFAULT 'unpaid',
  customer_notes TEXT NULL,
  internal_notes TEXT NULL,
  customer_signature MEDIUMBLOB NULL,
  customer_signature_user_id BIGINT UNSIGNED NULL,
  customer_signature_captured_by BIGINT UNSIGNED NULL,
  customer_signed_at DATETIME NULL,
  approval_note VARCHAR(500) NULL,
  approver_signature MEDIUMBLOB NULL,
  decision_by BIGINT UNSIGNED NULL,
  decision_at DATETIME NULL,
  cancellation_reason VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  confirmed_by BIGINT UNSIGNED NULL,
  confirmed_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_reservation_vehicle_period (vehicle_id,status,pickup_datetime,return_datetime),
  KEY idx_reservation_customer (customer_id,created_at), KEY idx_reservation_status_pickup (status,pickup_datetime),
  KEY idx_res_payment_status (payment_status,updated_at),
  CONSTRAINT fk_res_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_res_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_res_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_res_confirmer FOREIGN KEY (confirmed_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_res_customer_signature_user FOREIGN KEY (customer_signature_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_res_customer_signature_captor FOREIGN KEY (customer_signature_captured_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_res_decision_user FOREIGN KEY (decision_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_res_period CHECK (return_datetime > pickup_datetime),
  CONSTRAINT chk_res_amounts CHECK (agreed_daily_rate >= 0 AND estimated_days > 0 AND estimated_amount >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND security_deposit >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS driver_assignments (
  assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  driver_id BIGINT UNSIGNED NOT NULL,
  reservation_id BIGINT UNSIGNED NOT NULL UNIQUE,
  assigned_by BIGINT UNSIGNED NULL,
  notes VARCHAR(500) NULL,
  trip_status ENUM('assigned','en_route','on_site','completed','delayed') NOT NULL DEFAULT 'assigned',
  incident_report TEXT NULL,
  status_updated_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_driver_assignment_driver FOREIGN KEY (driver_id) REFERENCES drivers(driver_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_driver_assignment_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_driver_assignment_user FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  KEY idx_driver_assignment_driver (driver_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rentals (
  rental_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rental_number VARCHAR(40) NOT NULL UNIQUE,
  reservation_id BIGINT UNSIGNED NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  agreement_terms TEXT NULL,
  agreement_document_path VARCHAR(500) NULL,
  agreement_status ENUM('draft','issued','signed','active','completed','terminated') NOT NULL DEFAULT 'draft',
  signed_at DATETIME NULL,
  actual_pickup_datetime DATETIME NULL,
  expected_return_datetime DATETIME NOT NULL,
  actual_return_datetime DATETIME NULL,
  pickup_mileage INT UNSIGNED NULL,
  return_mileage INT UNSIGNED NULL,
  pickup_fuel_percent DECIMAL(5,2) NULL,
  return_fuel_percent DECIMAL(5,2) NULL,
  base_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  extra_mileage_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  late_return_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  damage_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  cleaning_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  other_charges DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  final_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('scheduled','active','overdue','returned','inspected','closed','cancelled') NOT NULL DEFAULT 'scheduled',
  pickup_notes TEXT NULL,
  return_notes TEXT NULL,
  handled_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_rental_vehicle_status (vehicle_id,status), KEY idx_rental_due (status,expected_return_datetime), KEY idx_rental_customer (customer_id,created_at),
  CONSTRAINT fk_rental_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_rental_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_rental_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_rental_handler FOREIGN KEY (handled_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_rental_dates CHECK (actual_return_datetime IS NULL OR actual_pickup_datetime IS NULL OR actual_return_datetime >= actual_pickup_datetime),
  CONSTRAINT chk_rental_fuel CHECK ((pickup_fuel_percent IS NULL OR pickup_fuel_percent BETWEEN 0 AND 100) AND (return_fuel_percent IS NULL OR return_fuel_percent BETWEEN 0 AND 100)),
  CONSTRAINT chk_rental_amounts CHECK (base_amount >= 0 AND extra_mileage_fee >= 0 AND late_return_fee >= 0 AND damage_fee >= 0 AND cleaning_fee >= 0 AND other_charges >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND final_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inspections (
  inspection_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rental_id BIGINT UNSIGNED NOT NULL,
  inspected_by BIGINT UNSIGNED NULL,
  inspection_type ENUM('pickup','return','routine','damage_followup') NOT NULL,
  inspected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  odometer_reading INT UNSIGNED NULL,
  fuel_level_percent DECIMAL(5,2) NULL,
  exterior_condition ENUM('excellent','good','fair','poor') NULL,
  interior_condition ENUM('excellent','good','fair','poor') NULL,
  cleanliness ENUM('clean','acceptable','needs_cleaning') NULL,
  damage_notes TEXT NULL,
  image_path VARCHAR(500) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_inspection_rental_type (rental_id,inspection_type),
  CONSTRAINT fk_inspection_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_inspection_user FOREIGN KEY (inspected_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_inspection_fuel CHECK (fuel_level_percent IS NULL OR fuel_level_percent BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
  invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(40) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  reservation_id BIGINT UNSIGNED NULL,
  rental_id BIGINT UNSIGNED NULL,
  issued_by BIGINT UNSIGNED NULL,
  issue_date DATE NOT NULL,
  due_date DATE NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'RWF',
  status ENUM('draft','issued','partially_paid','paid','overdue','void','refunded') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_invoice_customer_date (customer_id,issue_date), KEY idx_invoice_status_due (status,due_date),
  CONSTRAINT fk_invoice_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_invoice_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_invoice_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_invoice_issuer FOREIGN KEY (issued_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_invoice_amounts CHECK (subtotal >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND total_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_items (
  invoice_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  item_type ENUM('rental','driver','extra_mileage','late_return','damage','cleaning','fuel','delivery','discount','tax','other') NOT NULL DEFAULT 'other',
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_invoice_item_invoice (invoice_id),
  CONSTRAINT fk_item_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_invoice_item_values CHECK (quantity >= 0 AND unit_price >= 0 AND line_total >= 0)
) ENGINE=InnoDB;

-- One immutable ledger handles receipts, payment refunds, deposits, deductions and deposit returns.
-- Refund/deduction rows point to the original ledger row where relevant; never delete posted rows.
CREATE TABLE IF NOT EXISTS financial_transactions (
  transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_number VARCHAR(40) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  invoice_id BIGINT UNSIGNED NULL,
  reservation_id BIGINT UNSIGNED NULL,
  rental_id BIGINT UNSIGNED NULL,
  related_transaction_id BIGINT UNSIGNED NULL,
  transaction_type ENUM('payment','payment_refund','deposit_received','deposit_deduction','deposit_refund','adjustment') NOT NULL,
  adjustment_direction ENUM('credit','debit') NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method ENUM('cash','bank_transfer','card','mobile_money','online','other') NULL,
  external_reference VARCHAR(150) NULL UNIQUE,
  provider_name VARCHAR(100) NULL,
  status ENUM('pending','completed','failed','cancelled','awaiting_approval','rejected') NOT NULL DEFAULT 'completed',
  reason VARCHAR(500) NULL,
  processed_by BIGINT UNSIGNED NULL,
  approval_requested_by BIGINT UNSIGNED NULL,
  approval_reviewed_by BIGINT UNSIGNED NULL,
  approval_reviewed_at DATETIME NULL,
  approval_note VARCHAR(500) NULL,
  reconciled_at DATETIME NULL,
  reconciled_by BIGINT UNSIGNED NULL,
  reconciliation_note VARCHAR(500) NULL,
  transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_fin_invoice_date (invoice_id,transaction_date), KEY idx_fin_customer_date (customer_id,transaction_date), KEY idx_fin_approval_status (status,created_at), KEY idx_fin_reconcile (status,reconciled_at),
  KEY idx_fin_type_status (transaction_type,status),
  CONSTRAINT fk_fin_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_fin_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_fin_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_fin_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_fin_related FOREIGN KEY (related_transaction_id) REFERENCES financial_transactions(transaction_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_fin_processor FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_fin_approval_requester FOREIGN KEY (approval_requested_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_fin_approval_reviewer FOREIGN KEY (approval_reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_fin_reconciler FOREIGN KEY (reconciled_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_fin_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance_records (
  maintenance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maintenance_number VARCHAR(40) NOT NULL UNIQUE,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  reported_by BIGINT UNSIGNED NULL,
  maintenance_type ENUM('routine_service','repair','inspection','tyres','oil_change','bodywork','other') NOT NULL DEFAULT 'other',
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  service_provider VARCHAR(180) NULL,
  start_date DATE NOT NULL,
  expected_completion_date DATE NULL,
  completed_date DATE NULL,
  odometer_reading INT UNSIGNED NULL,
  cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  next_service_date DATE NULL,
  status ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_maintenance_vehicle_status (vehicle_id,status), KEY idx_maintenance_dates (start_date,expected_completion_date),
  CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_maintenance_reporter FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_maintenance_cost CHECK (cost >= 0),
  CONSTRAINT chk_maintenance_dates CHECK (expected_completion_date IS NULL OR expected_completion_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  customer_id BIGINT UNSIGNED NULL,
  channel ENUM('in_app','email','sms') NOT NULL DEFAULT 'in_app',
  notification_type VARCHAR(80) NOT NULL,
  subject VARCHAR(200) NULL,
  message TEXT NOT NULL,
  related_entity_type VARCHAR(60) NULL,
  related_entity_id BIGINT UNSIGNED NULL,
  status ENUM('queued','sent','delivered','failed','read') NOT NULL DEFAULT 'queued',
  scheduled_at DATETIME NULL,
  sent_at DATETIME NULL,
  read_at DATETIME NULL,
  error_message VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notification_user (user_id,status,created_at), KEY idx_notification_customer (customer_id,status,created_at), KEY idx_notification_queue (status,scheduled_at),
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_notification_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  audit_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_id VARCHAR(100) NULL,
  description VARCHAR(1000) NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  result ENUM('success','failure','denied') NOT NULL DEFAULT 'success',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_user_date (user_id,created_at), KEY idx_audit_entity (entity_type,entity_id), KEY idx_audit_action_date (action,created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
  setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  value_type ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
  description VARCHAR(255) NULL,
  is_sensitive TINYINT(1) NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_setting_user FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (role_name,description) VALUES
 ('System Administrator','Manage accounts, permissions and system configuration'),
 ('Business Manager','Manage operations and review reports'),
 ('Rental Officer','Manage vehicles, customers, reservations and rentals'),
 ('Accountant/Cashier','Manage invoices, transactions and refunds'),
 ('Vehicle Inspector/Maintenance Staff','Inspect vehicles and manage maintenance'),
 ('Customer','Access own reservations and invoices'),
 ('Driver','View assigned chauffeur trips');

INSERT IGNORE INTO permissions (permission_key,description) VALUES
 ('users.manage','Manage user accounts and roles'),('vehicles.view','View vehicles'),('vehicles.manage','Create and update vehicles'),
 ('customers.view','View customers'),('customers.manage','Create and update customers'),
 ('reservations.view','View reservations'),('reservations.manage','Create and manage reservations'),
 ('rentals.view','View rental records'),('rentals.manage','Manage pickup, return and rental records'),
 ('invoices.view','View invoices'),('invoices.manage','Create and manage invoices'),
 ('transactions.view','View payment and refund records'),('transactions.manage','Record payments, refunds and deposits'),
 ('transactions.approve','Approve or reject refund and financial adjustment requests'),
 ('maintenance.view','View maintenance records'),('maintenance.manage','Manage maintenance'),
 ('inspections.manage','Record vehicle inspections'),('reports.operational','View operational reports'),
 ('reports.financial','View financial reports'),('notifications.manage','Manage notifications'),
 ('audit.view','Review audit logs'),('settings.manage','Manage system settings');
INSERT IGNORE INTO permissions (permission_key,description) VALUES
 ('drivers.manage','Manage driver records and assignments'),('drivers.schedule','View assigned driver schedule and update your trips');

DELETE FROM role_permissions WHERE role_id IN (SELECT role_id FROM roles WHERE role_name='System Administrator');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('users.manage','vehicles.view','reservations.view','rentals.view','transactions.view','maintenance.view',
 'reports.operational','reports.financial','audit.view','settings.manage')
WHERE r.role_name='System Administrator';

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('vehicles.view','vehicles.manage','customers.view','customers.manage','reservations.view','reservations.manage','rentals.manage','invoices.view','reports.operational','reports.financial','maintenance.manage','inspections.manage','transactions.approve')
WHERE r.role_name='Business Manager';

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('vehicles.view','vehicles.manage','customers.view','customers.manage','reservations.view','reservations.manage','rentals.manage')
WHERE r.role_name='Rental Officer';

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('customers.view','reservations.view','invoices.view','invoices.manage','transactions.manage','reports.financial')
WHERE r.role_name='Accountant/Cashier';

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('vehicles.view','maintenance.manage','inspections.manage')
WHERE r.role_name='Vehicle Inspector/Maintenance Staff';

DELETE FROM role_permissions
WHERE role_id IN (SELECT role_id FROM roles WHERE role_name='Vehicle Inspector/Maintenance Staff')
  AND permission_id NOT IN (
    SELECT permission_id FROM permissions WHERE permission_key IN
      ('vehicles.view','maintenance.manage','inspections.manage')
  );

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('drivers.manage','drivers.schedule') WHERE r.role_name IN ('System Administrator','Business Manager');

DELETE FROM role_permissions
WHERE role_id IN (SELECT role_id FROM roles WHERE role_name='Driver')
  AND permission_id NOT IN (
    SELECT permission_id FROM permissions WHERE permission_key IN ('drivers.schedule')
  );

DELETE FROM role_permissions
WHERE role_id IN (SELECT role_id FROM roles WHERE role_name='Rental Officer')
  AND permission_id NOT IN (
    SELECT permission_id FROM permissions WHERE permission_key IN
      ('vehicles.view','vehicles.manage','customers.view','customers.manage','reservations.view','reservations.manage','rentals.manage')
  );

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key='drivers.schedule'
WHERE r.role_name='Driver';

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.role_id,p.permission_id FROM roles r JOIN permissions p ON p.permission_key IN
('vehicles.view','reservations.view','rentals.view','invoices.view') WHERE r.role_name='Customer';

DELETE FROM role_permissions
WHERE role_id IN (SELECT role_id FROM roles WHERE role_name='Customer')
  AND permission_id NOT IN (
    SELECT permission_id FROM permissions WHERE permission_key IN
      ('vehicles.view','reservations.view','rentals.view','invoices.view')
  );

INSERT IGNORE INTO vehicle_categories (category_name,description,default_daily_rate,default_weekly_rate,default_monthly_rate,passenger_capacity) VALUES
 ('Economy','Small fuel-efficient vehicles',25000,150000,500000,5),
 ('Sedan','Comfortable passenger cars',35000,210000,700000,5),
 ('SUV','Sport utility vehicles',60000,360000,1200000,7),
 ('Van','Group transport vehicles',80000,480000,1600000,9),
 ('Luxury','Premium vehicles',120000,720000,2400000,5);

INSERT IGNORE INTO system_settings (setting_key,setting_value,value_type,description) VALUES
 ('business_name','Car Rental Business','string','Business name displayed on documents'),
 ('business_country','Rwanda','string','Business country'),('default_currency','RWF','string','Currency for financial amounts'),
 ('timezone','Africa/Kigali','string','Business timezone'),
 ('reservation_confirmation_required','true','boolean','Reservations require staff confirmation'),
 ('default_deposit_amount','0','number','Business-defined default deposit');

CREATE OR REPLACE VIEW vw_invoice_balances AS
SELECT i.invoice_id,i.invoice_number,i.customer_id,i.total_amount,
 COALESCE(SUM(CASE WHEN ft.transaction_type='payment' AND ft.status='completed' THEN ft.amount
                   WHEN ft.transaction_type='payment_refund' AND ft.status='completed' THEN -ft.amount
                   WHEN ft.transaction_type='adjustment' AND ft.status='completed' AND ft.adjustment_direction='credit' THEN ft.amount
                   WHEN ft.transaction_type='adjustment' AND ft.status='completed' AND ft.adjustment_direction='debit' THEN -ft.amount ELSE 0 END),0) AS net_paid,
 GREATEST(i.total_amount-COALESCE(SUM(CASE WHEN ft.transaction_type='payment' AND ft.status='completed' THEN ft.amount
                   WHEN ft.transaction_type='payment_refund' AND ft.status='completed' THEN -ft.amount
                   WHEN ft.transaction_type='adjustment' AND ft.status='completed' AND ft.adjustment_direction='credit' THEN ft.amount
                   WHEN ft.transaction_type='adjustment' AND ft.status='completed' AND ft.adjustment_direction='debit' THEN -ft.amount ELSE 0 END),0),0) AS outstanding_balance,
 i.status
FROM invoices i LEFT JOIN financial_transactions ft ON ft.invoice_id=i.invoice_id
GROUP BY i.invoice_id,i.invoice_number,i.customer_id,i.total_amount,i.status;

-- REQUIRED APPLICATION RULES (not safely expressible as simple UNIQUE constraints):
-- 1. Confirm booking inside a transaction. Lock the vehicle row SELECT ... FOR UPDATE,
--    then query conflicting confirmed reservations where existing.pickup < requested.return
--    AND existing.return > requested.pickup. Reject conflict, maintenance blocks, inactive cars,
--    invalid date ranges, blacklisted customers, or expired licence where policy requires it.
-- 2. Use half-open periods [pickup, return): a return at 10:00 may be followed by pickup at 10:00
--    only if operational turnaround policy permits it. Otherwise add a turnaround buffer.
-- 3. Recheck conflicts at confirmation AND rental activation; do not rely on search results.
-- 4. When confirming, updating vehicle/rental status and reservation status, use one DB transaction.
-- 5. Compute invoice totals server-side from invoice_items using DECIMAL; never trust browser totals.
-- 6. Post financial ledger rows atomically. Posted rows are immutable; correct mistakes with reversal
--    transactions. Refunds must not exceed refundable completed payments. Deposit deductions/refunds
--    must not exceed the remaining deposit balance. Lock invoice/related transaction rows as needed.
-- 7. Recalculate invoice status/balance in a transaction after each posted payment/refund.
-- 8. Enforce ownership checks for customer-facing routes; role permissions alone are insufficient.
-- 9. Use prepared statements, CSRF protection, output escaping, secure sessions, password_hash/verify,
--    rate-limited login, least privilege, audit logging and verified backup/restore procedures.
-- 10. Use soft archival/status changes instead of deleting financial records or vehicles with history.
