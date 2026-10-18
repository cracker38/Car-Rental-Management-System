-- CRMS backup 2026-10-18T11:32:21+02:00
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `audit_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `result` enum('success','failure','denied') NOT NULL DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_log_id`),
  KEY `idx_audit_user_date` (`user_id`,`created_at`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_action_date` (`action`,`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('1',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 18:45:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('2',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 21:48:35');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('3',NULL,'csrf.rejected','security',NULL,'Invalid CSRF token',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9549','denied','2026-10-15 21:48:56');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('4',NULL,'user.registered','user','2','it.elias38@gmail.com',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-15 21:55:18');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('5',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 21:55:23');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('6',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 21:55:25');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('7',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 21:55:31');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('8',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 21:55:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('9',NULL,'login.throttled','user','admin@carrental.local','Too many failed attempts',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','denied','2026-10-15 21:55:48');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('10',NULL,'login.failed','user','admin@carrental.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-15 21:56:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('11',NULL,'login.throttled','user','admin@carrental.local','Too many failed attempts',NULL,NULL,'::1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','denied','2026-10-15 21:56:19');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('12',NULL,'login.throttled','user','admin@carrental.local','Too many failed attempts',NULL,NULL,'::1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','denied','2026-10-15 21:56:27');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('13',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','failure','2026-10-15 22:07:42');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('14',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-17 23:20:54');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('15',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-17 23:24:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('16',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-17 23:24:10');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('17',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-17 23:24:26');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('18',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-17 23:24:53');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('19','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/3.25.9 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36','success','2026-10-17 23:29:04');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('20',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 08:02:35');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('21','1','finance.posted','financial_transaction','1','TXN-20261018-34C16E payment 50.00',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/3.25.9 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36','success','2026-10-18 08:09:17');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('22',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 08:34:06');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('23','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','success','2026-10-18 09:38:42');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('24','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','success','2026-10-18 09:39:06');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('25',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 10:07:40');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('26',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 10:07:43');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('27',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 10:07:48');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('28',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 10:07:54');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('29',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 10:08:09');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('30',NULL,'login.failed','user','admin@carrental.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 11:11:01');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('31','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:12:25');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('32','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:16:02');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('33',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 11:16:15');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('34',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 11:16:22');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('35',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 11:16:49');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('36','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:19:28');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('37','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:26:42');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('38','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:26:45');
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `linked_user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_code` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `identification_type` enum('national_id','passport','other') DEFAULT NULL,
  `identification_number` varchar(100) DEFAULT NULL,
  `driving_license_number` varchar(100) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive','blacklisted') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  UNIQUE KEY `linked_user_id` (`linked_user_id`),
  UNIQUE KEY `uq_customer_identity` (`identification_type`,`identification_number`),
  KEY `idx_customer_name` (`last_name`,`first_name`),
  KEY `idx_customer_phone` (`phone`),
  KEY `idx_customer_email` (`email`),
  KEY `fk_customer_creator` (`created_by`),
  CONSTRAINT `fk_customer_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_customer_user` FOREIGN KEY (`linked_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `customers` (`customer_id`,`linked_user_id`,`customer_code`,`first_name`,`last_name`,`phone`,`email`,`address`,`identification_type`,`identification_number`,`driving_license_number`,`license_expiry_date`,`status`,`created_by`,`created_at`,`updated_at`) VALUES ('1','2','CUS-20261015-1877','Elias','DUKUZUMUREMYI','0785354935','it.elias38@gmail.com','kg43','national_id','1200280054353106','admin@carrental.local',NULL,'active','2','2026-10-15 21:55:18','2026-10-15 21:55:18');
DROP TABLE IF EXISTS `driver_assignments`;
CREATE TABLE `driver_assignments` (
  `assignment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint(20) unsigned NOT NULL,
  `reservation_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `trip_status` enum('assigned','en_route','on_site','completed','delayed') NOT NULL DEFAULT 'assigned',
  `incident_report` text DEFAULT NULL,
  `status_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `fk_driver_assignment_user` (`assigned_by`),
  KEY `idx_driver_assignment_driver` (`driver_id`),
  CONSTRAINT `fk_driver_assignment_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_driver_assignment_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_driver_assignment_user` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `drivers`;
CREATE TABLE `drivers` (
  `driver_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `driving_license_number` varchar(100) NOT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`driver_id`),
  UNIQUE KEY `driving_license_number` (`driving_license_number`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_driver_status_name` (`status`,`last_name`,`first_name`),
  KEY `fk_driver_creator` (`created_by`),
  CONSTRAINT `fk_driver_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_driver_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `financial_transactions`;
CREATE TABLE `financial_transactions` (
  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(40) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `reservation_id` bigint(20) unsigned DEFAULT NULL,
  `rental_id` bigint(20) unsigned DEFAULT NULL,
  `related_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_type` enum('payment','payment_refund','deposit_received','deposit_deduction','deposit_refund','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','card','mobile_money','online','other') DEFAULT NULL,
  `external_reference` varchar(150) DEFAULT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'completed',
  `reason` varchar(500) DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `transaction_number` (`transaction_number`),
  UNIQUE KEY `external_reference` (`external_reference`),
  KEY `idx_fin_invoice_date` (`invoice_id`,`transaction_date`),
  KEY `idx_fin_customer_date` (`customer_id`,`transaction_date`),
  KEY `idx_fin_type_status` (`transaction_type`,`status`),
  KEY `fk_fin_reservation` (`reservation_id`),
  KEY `fk_fin_rental` (`rental_id`),
  KEY `fk_fin_related` (`related_transaction_id`),
  KEY `fk_fin_processor` (`processed_by`),
  CONSTRAINT `fk_fin_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_related` FOREIGN KEY (`related_transaction_id`) REFERENCES `financial_transactions` (`transaction_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_fin_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `financial_transactions` (`transaction_id`,`transaction_number`,`customer_id`,`invoice_id`,`reservation_id`,`rental_id`,`related_transaction_id`,`transaction_type`,`amount`,`payment_method`,`external_reference`,`provider_name`,`status`,`reason`,`processed_by`,`transaction_date`,`created_at`) VALUES ('1','TXN-20261018-34C16E','1',NULL,NULL,NULL,NULL,'payment','50.00','mobile_money',NULL,'','completed','none','1','2026-10-18 08:09:17','2026-10-18 08:09:17');
DROP TABLE IF EXISTS `inspections`;
CREATE TABLE `inspections` (
  `inspection_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rental_id` bigint(20) unsigned NOT NULL,
  `inspected_by` bigint(20) unsigned DEFAULT NULL,
  `inspection_type` enum('pickup','return','routine','damage_followup') NOT NULL,
  `inspected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `odometer_reading` int(10) unsigned DEFAULT NULL,
  `fuel_level_percent` decimal(5,2) DEFAULT NULL,
  `exterior_condition` enum('excellent','good','fair','poor') DEFAULT NULL,
  `interior_condition` enum('excellent','good','fair','poor') DEFAULT NULL,
  `cleanliness` enum('clean','acceptable','needs_cleaning') DEFAULT NULL,
  `damage_notes` text DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`inspection_id`),
  KEY `idx_inspection_rental_type` (`rental_id`,`inspection_type`),
  KEY `fk_inspection_user` (`inspected_by`),
  CONSTRAINT `fk_inspection_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inspection_user` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_inspection_fuel` CHECK (`fuel_level_percent` is null or `fuel_level_percent` between 0 and 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `invoice_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `item_type` enum('rental','driver','extra_mileage','late_return','damage','cleaning','fuel','delivery','discount','tax','other') NOT NULL DEFAULT 'other',
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`invoice_item_id`),
  KEY `idx_invoice_item_invoice` (`invoice_id`),
  CONSTRAINT `fk_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_invoice_item_values` CHECK (`quantity` >= 0 and `unit_price` >= 0 and `line_total` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `invoice_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(40) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `reservation_id` bigint(20) unsigned DEFAULT NULL,
  `rental_id` bigint(20) unsigned DEFAULT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'RWF',
  `status` enum('draft','issued','partially_paid','paid','overdue','void','refunded') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_invoice_customer_date` (`customer_id`,`issue_date`),
  KEY `idx_invoice_status_due` (`status`,`due_date`),
  KEY `fk_invoice_reservation` (`reservation_id`),
  KEY `fk_invoice_rental` (`rental_id`),
  KEY `fk_invoice_issuer` (`issued_by`),
  CONSTRAINT `fk_invoice_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_issuer` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_invoice_amounts` CHECK (`subtotal` >= 0 and `discount_amount` >= 0 and `tax_amount` >= 0 and `total_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `maintenance_records`;
CREATE TABLE `maintenance_records` (
  `maintenance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `maintenance_number` varchar(40) NOT NULL,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `maintenance_type` enum('routine_service','repair','inspection','tyres','oil_change','bodywork','other') NOT NULL DEFAULT 'other',
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `service_provider` varchar(180) DEFAULT NULL,
  `start_date` date NOT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `odometer_reading` int(10) unsigned DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `next_service_date` date DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`maintenance_id`),
  UNIQUE KEY `maintenance_number` (`maintenance_number`),
  KEY `idx_maintenance_vehicle_status` (`vehicle_id`,`status`),
  KEY `idx_maintenance_dates` (`start_date`,`expected_completion_date`),
  KEY `fk_maintenance_reporter` (`reported_by`),
  CONSTRAINT `fk_maintenance_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_maintenance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_maintenance_cost` CHECK (`cost` >= 0),
  CONSTRAINT `chk_maintenance_dates` CHECK (`expected_completion_date` is null or `expected_completion_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `channel` enum('in_app','email','sms') NOT NULL DEFAULT 'in_app',
  `notification_type` varchar(80) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `related_entity_type` varchar(60) DEFAULT NULL,
  `related_entity_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('queued','sent','delivered','failed','read') NOT NULL DEFAULT 'queued',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_user` (`user_id`,`status`,`created_at`),
  KEY `idx_notification_customer` (`customer_id`,`status`,`created_at`),
  KEY `idx_notification_queue` (`status`,`scheduled_at`),
  CONSTRAINT `fk_notification_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `permission_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('1','users.manage','Manage user accounts and roles');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('2','vehicles.view','View vehicles');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('3','vehicles.manage','Create and update vehicles');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('4','customers.view','View customers');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('5','customers.manage','Create and update customers');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('6','reservations.view','View reservations');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('7','reservations.manage','Create and manage reservations');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('8','rentals.manage','Manage pickup, return and rental records');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('9','invoices.view','View invoices');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('10','invoices.manage','Create and manage invoices');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('11','transactions.manage','Record payments, refunds and deposits');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('12','maintenance.manage','Manage maintenance');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('13','inspections.manage','Record vehicle inspections');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('14','reports.operational','View operational reports');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('15','reports.financial','View financial reports');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('16','notifications.manage','Manage notifications');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('17','audit.view','Review audit logs');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('18','settings.manage','Manage system settings');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('37','drivers.manage','Manage driver records and assignments');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('38','drivers.schedule','View assigned driver schedule');
DROP TABLE IF EXISTS `rentals`;
CREATE TABLE `rentals` (
  `rental_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rental_number` varchar(40) NOT NULL,
  `reservation_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `agreement_terms` text DEFAULT NULL,
  `agreement_document_path` varchar(500) DEFAULT NULL,
  `agreement_status` enum('draft','issued','signed','active','completed','terminated') NOT NULL DEFAULT 'draft',
  `signed_at` datetime DEFAULT NULL,
  `actual_pickup_datetime` datetime DEFAULT NULL,
  `expected_return_datetime` datetime NOT NULL,
  `actual_return_datetime` datetime DEFAULT NULL,
  `pickup_mileage` int(10) unsigned DEFAULT NULL,
  `return_mileage` int(10) unsigned DEFAULT NULL,
  `pickup_fuel_percent` decimal(5,2) DEFAULT NULL,
  `return_fuel_percent` decimal(5,2) DEFAULT NULL,
  `base_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `extra_mileage_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_return_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `damage_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cleaning_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('scheduled','active','overdue','returned','inspected','closed','cancelled') NOT NULL DEFAULT 'scheduled',
  `pickup_notes` text DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `handled_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`rental_id`),
  UNIQUE KEY `rental_number` (`rental_number`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `idx_rental_vehicle_status` (`vehicle_id`,`status`),
  KEY `idx_rental_due` (`status`,`expected_return_datetime`),
  KEY `idx_rental_customer` (`customer_id`,`created_at`),
  KEY `fk_rental_handler` (`handled_by`),
  CONSTRAINT `fk_rental_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rental_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rental_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rental_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_rental_dates` CHECK (`actual_return_datetime` is null or `actual_pickup_datetime` is null or `actual_return_datetime` >= `actual_pickup_datetime`),
  CONSTRAINT `chk_rental_fuel` CHECK ((`pickup_fuel_percent` is null or `pickup_fuel_percent` between 0 and 100) and (`return_fuel_percent` is null or `return_fuel_percent` between 0 and 100)),
  CONSTRAINT `chk_rental_amounts` CHECK (`base_amount` >= 0 and `extra_mileage_fee` >= 0 and `late_return_fee` >= 0 and `damage_fee` >= 0 and `cleaning_fee` >= 0 and `other_charges` >= 0 and `discount_amount` >= 0 and `tax_amount` >= 0 and `final_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `reservation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reservation_code` varchar(40) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `pickup_datetime` datetime NOT NULL,
  `return_datetime` datetime NOT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `return_location` varchar(255) DEFAULT NULL,
  `agreed_daily_rate` decimal(12,2) NOT NULL,
  `estimated_days` decimal(8,2) NOT NULL,
  `estimated_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `security_deposit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','cancelled','rejected','completed','no_show') NOT NULL DEFAULT 'pending',
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `cancellation_reason` varchar(500) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`reservation_id`),
  UNIQUE KEY `reservation_code` (`reservation_code`),
  KEY `idx_reservation_vehicle_period` (`vehicle_id`,`status`,`pickup_datetime`,`return_datetime`),
  KEY `idx_reservation_customer` (`customer_id`,`created_at`),
  KEY `idx_reservation_status_pickup` (`status`,`pickup_datetime`),
  KEY `fk_res_creator` (`created_by`),
  KEY `fk_res_confirmer` (`confirmed_by`),
  CONSTRAINT `fk_res_confirmer` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_res_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_res_period` CHECK (`return_datetime` > `pickup_datetime`),
  CONSTRAINT `chk_res_amounts` CHECK (`agreed_daily_rate` >= 0 and `estimated_days` > 0 and `estimated_amount` >= 0 and `discount_amount` >= 0 and `tax_amount` >= 0 and `security_deposit` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','1');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','3');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','5');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','7');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','8');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','10');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','11');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','12');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','13');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','14');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','15');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','16');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','17');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','18');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','37');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','38');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','3');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','5');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','7');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','8');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','12');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','13');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','14');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','15');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','37');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','38');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','3');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','5');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','7');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','8');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','16');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','37');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','38');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','10');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','11');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','15');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('5','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('5','12');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('5','13');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('5','14');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('7','38');
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('1','System Administrator','Manage accounts, permissions and system configuration','2026-10-15 18:08:47');
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('2','Business Manager','Manage operations and review reports','2026-10-15 18:08:47');
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('3','Rental Officer','Manage vehicles, customers, reservations and rentals','2026-10-15 18:08:47');
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('4','Accountant/Cashier','Manage invoices, transactions and refunds','2026-10-15 18:08:47');
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('5','Vehicle Inspector/Maintenance Staff','Inspect vehicles and manage maintenance','2026-10-15 18:08:47');
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('6','Customer','Access own reservations and invoices','2026-10-15 18:08:47');
INSERT INTO `roles` (`role_id`,`role_name`,`description`,`created_at`) VALUES ('7','Driver','View assigned chauffeur trips','2026-10-15 21:43:57');
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `value_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `fk_setting_user` (`updated_by`),
  CONSTRAINT `fk_setting_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('1','business_name','Car Rental Business','string','Business name displayed on documents','0',NULL,'2026-10-15 18:08:47');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('2','business_country','Rwanda','string','Business country','0',NULL,'2026-10-15 18:08:47');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('3','default_currency','RWF','string','Currency for financial amounts','0',NULL,'2026-10-15 18:08:47');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('4','timezone','Africa/Kigali','string','Business timezone','0',NULL,'2026-10-15 18:08:47');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('5','reservation_confirmation_required','true','boolean','Reservations require staff confirmation','0',NULL,'2026-10-15 18:08:47');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('6','default_deposit_amount','0','number','Business-defined default deposit','0',NULL,'2026-10-15 18:08:47');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('13','tax_rate_percent','0','number','VAT/tax percent applied to (subtotal - discount). 0 until configured.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('14','turnaround_minutes','0','number','Buffer added after a booking before the next pickup. 0 allows adjacent half-open periods.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('15','extra_km_rate','0','number','RWF per km above included_km_per_day * billable days.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('16','included_km_per_day','0','number','Kilometres included per billable day before extra mileage fees.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('17','late_fee_per_hour','0','number','RWF per started hour after expected return.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('18','login_max_attempts','5','number','Failed logins per email+IP before throttle.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('19','login_window_minutes','15','number','Throttle window in minutes.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('20','hdev_callback_url','','string','Public HTTPS URL for hdev_callback.php. Localhost is not reachable from HDEV.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('21','hdev_sms_sender','CRMS','string','Approved HDEV SMS sender ID.','0',NULL,'2026-10-15 21:43:57');
INSERT INTO `system_settings` (`setting_id`,`setting_key`,`setting_value`,`value_type`,`description`,`is_sensitive`,`updated_by`,`updated_at`) VALUES ('22','hdev_sms_enabled','true','boolean','Send customer SMS via HDEV when true.','0',NULL,'2026-10-15 21:43:57');
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role_status` (`role_id`,`status`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('1','1','System','Administrator','admin@crms.local','+250785354935','$2y$10$aLpQE.Knwug2SZFkbZspgO.Ngw6o7DJykpvzm6CQjhe3KROytft0W','active','2026-10-18 11:26:45','2026-10-15 21:43:57','2026-10-18 11:26:45');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('2','6','Elias','DUKUZUMUREMYI','it.elias38@gmail.com','0785354935','$2y$10$yHFBrSXBHOPya47LcpISeuGS0HjiJ3d7zXoZ6MKja5uMesludGsAm','active',NULL,'2026-10-15 21:55:18','2026-10-15 21:55:18');
DROP TABLE IF EXISTS `vehicle_categories`;
CREATE TABLE `vehicle_categories` (
  `category_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `default_daily_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `default_weekly_rate` decimal(12,2) DEFAULT NULL,
  `default_monthly_rate` decimal(12,2) DEFAULT NULL,
  `passenger_capacity` tinyint(3) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`),
  CONSTRAINT `chk_category_rates` CHECK (`default_daily_rate` >= 0 and (`default_weekly_rate` is null or `default_weekly_rate` >= 0) and (`default_monthly_rate` is null or `default_monthly_rate` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `vehicle_categories` (`category_id`,`category_name`,`description`,`default_daily_rate`,`default_weekly_rate`,`default_monthly_rate`,`passenger_capacity`,`is_active`,`created_at`) VALUES ('1','Economy','Small fuel-efficient vehicles','25000.00','150000.00','500000.00','5','1','2026-10-15 18:08:47');
INSERT INTO `vehicle_categories` (`category_id`,`category_name`,`description`,`default_daily_rate`,`default_weekly_rate`,`default_monthly_rate`,`passenger_capacity`,`is_active`,`created_at`) VALUES ('2','Sedan','Comfortable passenger cars','35000.00','210000.00','700000.00','5','1','2026-10-15 18:08:47');
INSERT INTO `vehicle_categories` (`category_id`,`category_name`,`description`,`default_daily_rate`,`default_weekly_rate`,`default_monthly_rate`,`passenger_capacity`,`is_active`,`created_at`) VALUES ('3','SUV','Sport utility vehicles','60000.00','360000.00','1200000.00','7','1','2026-10-15 18:08:47');
INSERT INTO `vehicle_categories` (`category_id`,`category_name`,`description`,`default_daily_rate`,`default_weekly_rate`,`default_monthly_rate`,`passenger_capacity`,`is_active`,`created_at`) VALUES ('4','Van','Group transport vehicles','80000.00','480000.00','1600000.00','9','1','2026-10-15 18:08:47');
INSERT INTO `vehicle_categories` (`category_id`,`category_name`,`description`,`default_daily_rate`,`default_weekly_rate`,`default_monthly_rate`,`passenger_capacity`,`is_active`,`created_at`) VALUES ('5','Luxury','Premium vehicles','120000.00','720000.00','2400000.00','5','1','2026-10-15 18:08:47');
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `vehicle_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `registration_no` varchar(30) NOT NULL,
  `vin` varchar(80) DEFAULT NULL,
  `make` varchar(80) NOT NULL,
  `model` varchar(80) NOT NULL,
  `manufacture_year` smallint(5) unsigned DEFAULT NULL,
  `colour` varchar(40) DEFAULT NULL,
  `transmission` enum('manual','automatic','other') NOT NULL DEFAULT 'manual',
  `fuel_type` enum('petrol','diesel','hybrid','electric','other') NOT NULL DEFAULT 'petrol',
  `daily_rate` decimal(12,2) NOT NULL,
  `weekly_rate` decimal(12,2) DEFAULT NULL,
  `monthly_rate` decimal(12,2) DEFAULT NULL,
  `current_mileage` int(10) unsigned NOT NULL DEFAULT 0,
  `fuel_level_percent` decimal(5,2) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('available','reserved','rented','maintenance','inactive') NOT NULL DEFAULT 'available',
  `insurance_expiry_date` date DEFAULT NULL,
  `inspection_expiry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `registration_no` (`registration_no`),
  UNIQUE KEY `vin` (`vin`),
  KEY `idx_vehicle_search` (`category_id`,`status`,`daily_rate`),
  KEY `idx_vehicle_model` (`make`,`model`),
  KEY `fk_vehicle_creator` (`created_by`),
  CONSTRAINT `fk_vehicle_category` FOREIGN KEY (`category_id`) REFERENCES `vehicle_categories` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vehicle_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_vehicle_rates` CHECK (`daily_rate` >= 0 and (`weekly_rate` is null or `weekly_rate` >= 0) and (`monthly_rate` is null or `monthly_rate` >= 0)),
  CONSTRAINT `chk_vehicle_fuel` CHECK (`fuel_level_percent` is null or `fuel_level_percent` between 0 and 100)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `vehicles` (`vehicle_id`,`category_id`,`registration_no`,`vin`,`make`,`model`,`manufacture_year`,`colour`,`transmission`,`fuel_type`,`daily_rate`,`weekly_rate`,`monthly_rate`,`current_mileage`,`fuel_level_percent`,`image_path`,`status`,`insurance_expiry_date`,`inspection_expiry_date`,`description`,`created_by`,`created_at`,`updated_at`) VALUES ('1','2','RAD-001-A',NULL,'Toyota','Corolla','2020','White','automatic','petrol','35000.00','210000.00','700000.00','12000',NULL,'public/img/fleet/default.svg','available',NULL,NULL,NULL,'1','2026-10-15 21:43:57','2026-10-15 21:43:57');
INSERT INTO `vehicles` (`vehicle_id`,`category_id`,`registration_no`,`vin`,`make`,`model`,`manufacture_year`,`colour`,`transmission`,`fuel_type`,`daily_rate`,`weekly_rate`,`monthly_rate`,`current_mileage`,`fuel_level_percent`,`image_path`,`status`,`insurance_expiry_date`,`inspection_expiry_date`,`description`,`created_by`,`created_at`,`updated_at`) VALUES ('2','2','RAD-002-B',NULL,'Honda','Civic','2021','Silver','automatic','petrol','38000.00','228000.00','760000.00','8000',NULL,'public/img/fleet/default.svg','available',NULL,NULL,NULL,'1','2026-10-15 21:43:57','2026-10-15 21:43:57');
INSERT INTO `vehicles` (`vehicle_id`,`category_id`,`registration_no`,`vin`,`make`,`model`,`manufacture_year`,`colour`,`transmission`,`fuel_type`,`daily_rate`,`weekly_rate`,`monthly_rate`,`current_mileage`,`fuel_level_percent`,`image_path`,`status`,`insurance_expiry_date`,`inspection_expiry_date`,`description`,`created_by`,`created_at`,`updated_at`) VALUES ('3','3','RAD-100-S',NULL,'Toyota','RAV4','2022','Black','automatic','petrol','60000.00','360000.00','1200000.00','5000',NULL,'public/img/fleet/default.svg','available',NULL,NULL,NULL,'1','2026-10-15 21:43:57','2026-10-15 21:43:57');
DROP TABLE IF EXISTS `vw_invoice_balances`;
;
SET FOREIGN_KEY_CHECKS=1;
