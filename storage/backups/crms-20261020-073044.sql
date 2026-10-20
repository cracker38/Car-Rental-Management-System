-- CRMS backup 2026-10-20T07:30:44+02:00
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
) ENGINE=InnoDB AUTO_INCREMENT=269 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('39','1','backup.created','backup','crms-20261018-113221.sql','SQL export written',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:32:21');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('40','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:37:48');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('41','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:37:50');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('42','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:37:55');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('43','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:38:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('44','1','user.created','user','3','manager@crms.local',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:45:44');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('45','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:45:46');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('46','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:45:52');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('47','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:46:06');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('48','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:46:09');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('49','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:55:56');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('50','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:56:09');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('51','1','user.created','user','4','accountant@crms.local',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:57:05');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('52','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:57:08');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('53','4','login.success','user','4','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 11:57:26');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('54','4','logout','user','4','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:29:23');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('55','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:29:29');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('56','1','user.created','user','5','rental@crms.local',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:29:46');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('57','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:29:48');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('58','5','login.success','user','5','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:29:56');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('59','5','logout','user','5','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:44:07');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('60','5','login.success','user','5','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:44:10');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('61','5','logout','user','5','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:54:38');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('62','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:54:43');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('63','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:55:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('64','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:55:43');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('65','1','user.created','user','6','inspector@crms.local',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:56:10');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('66','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:56:12');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('67','6','login.success','user','6','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 12:56:21');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('68','6','maintenance.created','maintenance','1','MNT-20261018-135623',NULL,'{\"vehicle_id\":1,\"status\":\"scheduled\",\"cost\":\"0\",\"next_service_date\":\"2026-10-29\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:02:54');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('69','6','maintenance.status_changed','maintenance','1','MNT-20261018-135623','{\"status\":\"scheduled\"}','{\"status\":\"cancelled\",\"reason\":\"done\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:03:43');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('70','6','maintenance.details_updated','maintenance','1','MNT-20261018-135623','{\"title\":\"Toyota Corolla\",\"description\":\"Toyota Corolla\",\"cost\":\"0.00\",\"odometer_reading\":null,\"next_service_date\":\"2026-10-29\"}','{\"title\":\"Toyota Corolla\",\"description\":\"Toyota Corolla\",\"cost\":\"0.00\",\"odometer_reading\":null,\"next_service_date\":\"2026-10-18\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:05:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('71','6','maintenance.details_updated','maintenance','1','MNT-20261018-135623','{\"title\":\"Toyota Corolla\",\"description\":\"Toyota Corolla\",\"cost\":\"0.00\",\"odometer_reading\":null,\"next_service_date\":\"2026-10-18\"}','{\"title\":\"Toyota Corolla\",\"description\":\"Toyota Corolla\",\"cost\":\"0.00\",\"odometer_reading\":null,\"next_service_date\":\"2026-10-17\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:05:46');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('72','6','logout','user','6','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:06:16');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('73','6','login.success','user','6','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:10:47');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('74','6','logout','user','6','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:11:15');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('75','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:12:13');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('76','1','user.created','user','7','driver@crms.local',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:12:32');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('77','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:12:34');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('78','7','login.success','user','7','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:12:42');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('79','7','logout','user','7','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:22:19');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('80','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:24:37');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('81','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:24:38');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('82',NULL,'login.failed','user','it.elias38@gmail.com','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-18 13:25:34');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('83','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:25:40');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('84','1','user.updated','user','2',NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:26:04');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('85','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:26:24');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('86',NULL,'user.registered','user','9','urhe665@gmail.com',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:27:21');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('87','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:27:38');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('88','9','authz.denied','permission','rentals.view','Missing permission',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','denied','2026-10-18 13:30:36');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('89','9','authz.denied','permission','rentals.view','Missing permission',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','denied','2026-10-18 13:30:46');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('90','9','authz.denied','permission','rentals.view','Missing permission',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','denied','2026-10-18 13:30:51');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('91','9','logout','user','9','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:37:10');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('92','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:42:24');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('93','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:42:25');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('94','9','reservation.created','reservation','1','RSV-20261018-42B343 pending',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:43:37');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('95','9','sms.failed','notification','1','reservation_created to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:43:37');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('96','9','reservation.created','reservation','2','RSV-20261018-F50593 pending',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:43:37');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('97','9','sms.failed','notification','2','reservation_created to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:43:37');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('98','9','logout','user','9','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 13:45:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('99','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:01:24');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('100','9','invoice.created','invoice','1','INV-20261018-E6C8A1 booking prepayment quote',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:01:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('101','9','reservation.updated','reservation','1','RSV-20261018-42B343','{\"reservation_id\":1,\"reservation_code\":\"RSV-20261018-42B343\",\"customer_id\":3,\"vehicle_id\":2,\"pickup_datetime\":\"2026-10-20 13:43:00\",\"return_datetime\":\"2026-10-21 13:43:00\",\"pickup_location\":\"kirehe\",\"return_location\":\"kamonyo\",\"agreed_daily_rate\":\"38000.00\",\"estimated_days\":\"1.00\",\"estimated_amount\":\"38000.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"0.00\",\"security_deposit\":\"0.00\",\"status\":\"pending\",\"customer_notes\":\"i like it\",\"internal_notes\":null,\"cancellation_reason\":null,\"created_by\":9,\"confirmed_by\":null,\"confirmed_at\":null,\"cancelled_at\":null,\"created_at\":\"2026-10-18 13:43:37\",\"updated_at\":\"2026-10-18 13:43:37\"}','{\"vehicle_id\":2,\"pickup_datetime\":\"2026-10-20 13:43:00\",\"return_datetime\":\"2026-10-21 13:43:00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:01:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('102','9','sms.failed','notification','1','reservation_updated to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:01:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('103','9','invoice.created','invoice','2','INV-20261018-592B84 booking prepayment quote',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:03:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('104','9','reservation.created','reservation','3','RSV-20261018-D17B04 pending',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:03:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('105','9','sms.failed','notification','3','reservation_created to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:03:03');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('106','9','logout','user','9','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:03:58');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('107','4','login.success','user','4','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:04:05');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('108','4','invoice.issued','invoice','2','INV-20261018-592B84',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:04:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('109','4','sms.failed','notification','2','invoice_issued to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:04:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('110','4','logout','user','4','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:06:45');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('111','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:06:53');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('112','9','logout','user','9','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:08:56');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('113','4','login.success','user','4','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:09:01');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('114','4','logout','user','4','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:10:43');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('115','5','login.success','user','5','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:11:30');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('116','5','logout','user','5','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:12:29');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('117','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:13:38');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('118','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:17:52');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('119','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:17:58');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('120','9','logout','user','9','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:19:28');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('121','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:19:39');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('122','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:20:13');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('123','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:20:25');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('124','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:20:41');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('125','5','login.success','user','5','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:20:48');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('126','5','logout','user','5','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:21:12');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('127','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:22:30');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('128','3','invoice.voided','invoice','2','Reservation updated before payment.',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:23:49');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('129','3','invoice.created','invoice','3','INV-20261018-179F1A booking prepayment quote',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:23:49');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('130','3','reservation.updated','reservation','3','RSV-20261018-D17B04','{\"reservation_id\":3,\"reservation_code\":\"RSV-20261018-D17B04\",\"customer_id\":3,\"vehicle_id\":2,\"pickup_datetime\":\"2026-10-19 16:04:00\",\"return_datetime\":\"2026-10-28 04:04:00\",\"pickup_location\":\"kirehe\",\"return_location\":\"kamonyo\",\"agreed_daily_rate\":\"38000.00\",\"estimated_days\":\"9.00\",\"estimated_amount\":\"304000.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"0.00\",\"security_deposit\":\"0.00\",\"status\":\"pending\",\"customer_notes\":\"yguhijop[\'\",\"internal_notes\":null,\"cancellation_reason\":null,\"created_by\":9,\"confirmed_by\":null,\"confirmed_at\":null,\"cancelled_at\":null,\"created_at\":\"2026-10-18 14:03:03\",\"updated_at\":\"2026-10-18 14:03:03\"}','{\"vehicle_id\":2,\"pickup_datetime\":\"2026-10-19 16:04:00\",\"return_datetime\":\"2026-10-28 04:04:00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:23:49');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('131','3','sms.failed','notification','3','reservation_updated to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:23:49');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('132','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:24:07');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('133','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:24:16');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('134','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:33:47');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('135','1','logout','user','1','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:35:06');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('136','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:35:13');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('137','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:35:35');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('138','5','login.success','user','5','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:35:40');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('139','5','logout','user','5','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:36:17');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('140','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:36:24');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('141','3','invoice.issued','invoice','1','INV-20261018-E6C8A1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:15');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('142','3','invoice.booking_issued','invoice','1','INV-20261018-E6C8A1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:15');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('143','3','reservation.status','reservation','1','pending -> confirmed','{\"status\":\"pending\"}','{\"status\":\"confirmed\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:15');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('144','3','sms.failed','notification','1','reservation_status to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:15');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('145','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:21');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('146','9','login.success','user','9','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:27');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('147','9','hdev.pay_initiated','financial_transaction','2','CRMS-C226DBFBE373 failed',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:38:52');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('148','9','hdev.pay_initiated','financial_transaction','3','CRMS-6BD670E1C957 failed',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:39:07');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('149','9','logout','user','9','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:39:42');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('150','4','login.success','user','4','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:39:52');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('151','4','logout','user','4','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-18 14:40:37');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('200','3','login.success','user','3','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 06:57:39');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('201','3','invoice.voided','invoice','1','Reservation updated before payment.',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:08:44');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('202','3','invoice.created','invoice','12','INV-20261020-E3960D booking prepayment quote',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:08:44');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('203','3','reservation.updated','reservation','1','RSV-20261018-42B343','{\"reservation_id\":1,\"reservation_code\":\"RSV-20261018-42B343\",\"customer_id\":3,\"vehicle_id\":2,\"pickup_datetime\":\"2026-10-20 13:43:00\",\"return_datetime\":\"2026-10-21 13:43:00\",\"pickup_location\":\"kirehe\",\"return_location\":\"kamonyo\",\"agreed_daily_rate\":\"38000.00\",\"estimated_days\":\"1.00\",\"estimated_amount\":\"38000.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"0.00\",\"security_deposit\":\"0.00\",\"status\":\"confirmed\",\"payment_status\":\"unpaid\",\"customer_notes\":\"i like it\",\"internal_notes\":null,\"customer_signature\":\"[redacted]\",\"customer_signature_user_id\":\"[redacted]\",\"customer_signature_captured_by\":\"[redacted]\",\"customer_signed_at\":null,\"approval_note\":null,\"approver_signature\":\"[redacted]\",\"decision_by\":null,\"decision_at\":null,\"cancellation_reason\":null,\"created_by\":9,\"confirmed_by\":3,\"confirmed_at\":\"2026-10-18 14:38:15\",\"cancelled_at\":null,\"created_at\":\"2026-10-18 13:43:37\",\"updated_at\":\"2026-10-18 14:38:15\"}','{\"vehicle_id\":2,\"pickup_datetime\":\"2026-10-20 13:43:00\",\"return_datetime\":\"2026-10-21 13:43:00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:08:44');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('204','3','sms.failed','notification','1','reservation_updated to customer 3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:08:44');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('205','3','vehicle.readiness_released','vehicle','1','RAD-001-A','{\"status\":\"maintenance\"}','{\"status\":\"available\",\"inspection_note\":\"hgfhujkijlllllllllllll\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:12:49');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('266','3','logout','user','3','Signed out',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:24:25');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('267',NULL,'login.failed','user','admins@crms.local','Invalid credentials',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','failure','2026-10-20 07:24:33');
INSERT INTO `audit_logs` (`audit_log_id`,`user_id`,`action`,`entity_type`,`entity_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`result`,`created_at`) VALUES ('268','1','login.success','user','1','Signed in',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','success','2026-10-20 07:24:40');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `customers` (`customer_id`,`linked_user_id`,`customer_code`,`first_name`,`last_name`,`phone`,`email`,`address`,`identification_type`,`identification_number`,`driving_license_number`,`license_expiry_date`,`status`,`created_by`,`created_at`,`updated_at`) VALUES ('1',NULL,'CUS-20261015-1877','Elias','DUKUZUMUREMYI','0785354935','it.elias38@gmail.com','kg43','national_id','1200280054353106','admin@carrental.local',NULL,'active',NULL,'2026-10-15 21:55:18','2026-10-15 21:55:18');
INSERT INTO `customers` (`customer_id`,`linked_user_id`,`customer_code`,`first_name`,`last_name`,`phone`,`email`,`address`,`identification_type`,`identification_number`,`driving_license_number`,`license_expiry_date`,`status`,`created_by`,`created_at`,`updated_at`) VALUES ('3','9','CUS-20261018-CDC8','DUKUZUMUREMYI','Elias','0785354935','urhe665@gmail.com','KN123',NULL,NULL,'admins@crms.local',NULL,'active','9','2026-10-18 13:27:21','2026-10-18 13:27:21');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `status` enum('pending','completed','failed','cancelled','awaiting_approval','rejected') NOT NULL DEFAULT 'completed',
  `reason` varchar(500) DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adjustment_direction` enum('credit','debit') DEFAULT NULL,
  `approval_requested_by` bigint(20) unsigned DEFAULT NULL,
  `approval_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `approval_reviewed_at` datetime DEFAULT NULL,
  `approval_note` varchar(500) DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `reconciled_by` bigint(20) unsigned DEFAULT NULL,
  `reconciliation_note` varchar(500) DEFAULT NULL,
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
  KEY `idx_fin_approval_status` (`status`,`created_at`),
  KEY `idx_fin_reconcile` (`status`,`reconciled_at`),
  KEY `fk_fin_approval_requester` (`approval_requested_by`),
  KEY `fk_fin_approval_reviewer` (`approval_reviewed_by`),
  KEY `fk_fin_reconciler` (`reconciled_by`),
  CONSTRAINT `fk_fin_approval_requester` FOREIGN KEY (`approval_requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_approval_reviewer` FOREIGN KEY (`approval_reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_reconciler` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_related` FOREIGN KEY (`related_transaction_id`) REFERENCES `financial_transactions` (`transaction_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_fin_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `financial_transactions` (`transaction_id`,`transaction_number`,`customer_id`,`invoice_id`,`reservation_id`,`rental_id`,`related_transaction_id`,`transaction_type`,`amount`,`payment_method`,`external_reference`,`provider_name`,`status`,`reason`,`processed_by`,`transaction_date`,`created_at`,`adjustment_direction`,`approval_requested_by`,`approval_reviewed_by`,`approval_reviewed_at`,`approval_note`,`reconciled_at`,`reconciled_by`,`reconciliation_note`) VALUES ('1','TXN-20261018-34C16E','1',NULL,NULL,NULL,NULL,'payment','50.00','mobile_money',NULL,'','completed','none','1','2026-10-18 08:09:17','2026-10-18 08:09:17',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `financial_transactions` (`transaction_id`,`transaction_number`,`customer_id`,`invoice_id`,`reservation_id`,`rental_id`,`related_transaction_id`,`transaction_type`,`amount`,`payment_method`,`external_reference`,`provider_name`,`status`,`reason`,`processed_by`,`transaction_date`,`created_at`,`adjustment_direction`,`approval_requested_by`,`approval_reviewed_by`,`approval_reviewed_at`,`approval_note`,`reconciled_at`,`reconciled_by`,`reconciliation_note`) VALUES ('2','TXN-20261018-40FD01','3','1',NULL,NULL,NULL,'payment','38000.00','mobile_money','CRMS-C226DBFBE373','HDEV','failed','HDEV mobile money | gateway: Check the phone number, amount and API credentials.','9','2026-10-18 14:38:44','2026-10-18 14:38:44',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `financial_transactions` (`transaction_id`,`transaction_number`,`customer_id`,`invoice_id`,`reservation_id`,`rental_id`,`related_transaction_id`,`transaction_type`,`amount`,`payment_method`,`external_reference`,`provider_name`,`status`,`reason`,`processed_by`,`transaction_date`,`created_at`,`adjustment_direction`,`approval_requested_by`,`approval_reviewed_by`,`approval_reviewed_at`,`approval_note`,`reconciled_at`,`reconciled_by`,`reconciliation_note`) VALUES ('3','TXN-20261018-9B4891','3','1',NULL,NULL,NULL,'payment','1.00','mobile_money','CRMS-6BD670E1C957','HDEV','failed','HDEV mobile money | gateway: Check the phone number, amount and API credentials.','9','2026-10-18 14:39:06','2026-10-18 14:39:06',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `invoice_items` (`invoice_item_id`,`invoice_id`,`item_type`,`description`,`quantity`,`unit_price`,`line_total`,`created_at`) VALUES ('1','1','rental','Estimated vehicle hire â€” booking prepayment','1.00','38000.00','38000.00','2026-10-18 14:01:41');
INSERT INTO `invoice_items` (`invoice_item_id`,`invoice_id`,`item_type`,`description`,`quantity`,`unit_price`,`line_total`,`created_at`) VALUES ('2','2','rental','Estimated vehicle hire â€” booking prepayment','1.00','304000.00','304000.00','2026-10-18 14:03:03');
INSERT INTO `invoice_items` (`invoice_item_id`,`invoice_id`,`item_type`,`description`,`quantity`,`unit_price`,`line_total`,`created_at`) VALUES ('3','3','rental','Estimated vehicle hire â€” booking prepayment','1.00','304000.00','304000.00','2026-10-18 14:23:49');
INSERT INTO `invoice_items` (`invoice_item_id`,`invoice_id`,`item_type`,`description`,`quantity`,`unit_price`,`line_total`,`created_at`) VALUES ('12','12','rental','Estimated vehicle hire â€” booking prepayment','1.00','38000.00','38000.00','2026-10-20 07:08:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `invoices` (`invoice_id`,`invoice_number`,`customer_id`,`reservation_id`,`rental_id`,`issued_by`,`issue_date`,`due_date`,`subtotal`,`discount_amount`,`tax_amount`,`total_amount`,`currency`,`status`,`notes`,`created_at`,`updated_at`) VALUES ('1','INV-20261018-E6C8A1','3','1',NULL,'9','2026-10-18','2026-10-18','38000.00','0.00','0.00','38000.00','RWF','void','Booking prepayment: estimated vehicle hire. Approved additional charges, if any, are invoiced after return inspection.\nVoid: Reservation updated before payment.','2026-10-18 14:01:41','2026-10-20 07:08:44');
INSERT INTO `invoices` (`invoice_id`,`invoice_number`,`customer_id`,`reservation_id`,`rental_id`,`issued_by`,`issue_date`,`due_date`,`subtotal`,`discount_amount`,`tax_amount`,`total_amount`,`currency`,`status`,`notes`,`created_at`,`updated_at`) VALUES ('2','INV-20261018-592B84','3','3',NULL,'9','2026-10-18',NULL,'304000.00','0.00','0.00','304000.00','RWF','void','Booking prepayment: estimated vehicle hire. Approved additional charges, if any, are invoiced after return inspection.\nVoid: Reservation updated before payment.','2026-10-18 14:03:03','2026-10-18 14:23:49');
INSERT INTO `invoices` (`invoice_id`,`invoice_number`,`customer_id`,`reservation_id`,`rental_id`,`issued_by`,`issue_date`,`due_date`,`subtotal`,`discount_amount`,`tax_amount`,`total_amount`,`currency`,`status`,`notes`,`created_at`,`updated_at`) VALUES ('3','INV-20261018-179F1A','3','3',NULL,'3','2026-10-18',NULL,'304000.00','0.00','0.00','304000.00','RWF','draft','Booking prepayment: estimated vehicle hire. Approved additional charges, if any, are invoiced after return inspection.','2026-10-18 14:23:49','2026-10-18 14:23:49');
INSERT INTO `invoices` (`invoice_id`,`invoice_number`,`customer_id`,`reservation_id`,`rental_id`,`issued_by`,`issue_date`,`due_date`,`subtotal`,`discount_amount`,`tax_amount`,`total_amount`,`currency`,`status`,`notes`,`created_at`,`updated_at`) VALUES ('12','INV-20261020-E3960D','3','1',NULL,'3','2026-10-20',NULL,'38000.00','0.00','0.00','38000.00','RWF','draft','Booking prepayment: estimated vehicle hire. Approved additional charges, if any, are invoiced after return inspection.','2026-10-20 07:08:44','2026-10-20 07:08:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `maintenance_records` (`maintenance_id`,`maintenance_number`,`vehicle_id`,`reported_by`,`maintenance_type`,`title`,`description`,`service_provider`,`start_date`,`expected_completion_date`,`completed_date`,`odometer_reading`,`cost`,`next_service_date`,`status`,`notes`,`created_at`,`updated_at`) VALUES ('1','MNT-20261018-135623','1','6','routine_service','Toyota Corolla','Toyota Corolla','Toyota Corolla','2026-10-13','2026-10-15',NULL,NULL,'0.00','2026-10-17','cancelled','Toyota Corolla','2026-10-18 13:02:54','2026-10-18 13:05:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=174 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('1','9','3','in_app','reservation_created','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 is pending.','reservation','1','sent',NULL,'2026-10-18 13:43:37',NULL,NULL,'2026-10-18 13:43:37');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('2','5',NULL,'in_app','reservation_created','Reservation RSV-20261018-42B343','A new reservation was created and is pending.','reservation','1','sent',NULL,'2026-10-18 13:43:37',NULL,NULL,'2026-10-18 13:43:37');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('3','9','3','sms','reservation_created','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 is pending. Pickup 2026-10-20 13:43:00.','reservation','1','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 13:43:37');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('4','9','3','in_app','reservation_created','Reservation RSV-20261018-F50593','Reservation RSV-20261018-F50593 is pending.','reservation','2','sent',NULL,'2026-10-18 13:43:37',NULL,NULL,'2026-10-18 13:43:37');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('5','5',NULL,'in_app','reservation_created','Reservation RSV-20261018-F50593','A new reservation was created and is pending.','reservation','2','sent',NULL,'2026-10-18 13:43:37',NULL,NULL,'2026-10-18 13:43:37');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('6','9','3','sms','reservation_created','Reservation RSV-20261018-F50593','Reservation RSV-20261018-F50593 is pending. Pickup 2026-10-20 13:43:00.','reservation','2','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 13:43:37');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('7','9','3','in_app','reservation_updated','Reservation RSV-20261018-42B343','Reservation details have been updated.','reservation','1','sent',NULL,'2026-10-18 14:01:41',NULL,NULL,'2026-10-18 14:01:41');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('8','5',NULL,'in_app','reservation_updated','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 was updated.','reservation','1','sent',NULL,'2026-10-18 14:01:41',NULL,NULL,'2026-10-18 14:01:41');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('9','9','3','sms','reservation_updated','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 details were updated. Pickup 2026-10-20 13:43:00.','reservation','1','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 14:01:41');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('10','9','3','in_app','reservation_created','Reservation RSV-20261018-D17B04','Reservation RSV-20261018-D17B04 is pending. Pickup 2026-10-19 16:04:00. The booking invoice will be issued after staff confirms the reservation.','reservation','3','sent',NULL,'2026-10-18 14:03:03',NULL,NULL,'2026-10-18 14:03:03');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('11','5',NULL,'in_app','reservation_created','Reservation RSV-20261018-D17B04','A new reservation was created. Reservation RSV-20261018-D17B04 is pending. Pickup 2026-10-19 16:04:00. The booking invoice will be issued after staff confirms the reservation.','reservation','3','sent',NULL,'2026-10-18 14:03:03',NULL,NULL,'2026-10-18 14:03:03');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('12','9','3','sms','reservation_created','Reservation RSV-20261018-D17B04','Reservation RSV-20261018-D17B04 is pending. Pickup 2026-10-19 16:04:00. The booking invoice will be issued after staff confirmation.','reservation','3','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 14:03:03');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('13','9','3','sms','invoice_issued','Invoice INV-20261018-592B84','Invoice INV-20261018-592B84 of RWF 304000 has been issued.','invoice','2','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 14:04:41');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('14','3','3','in_app','reservation_updated','Reservation RSV-20261018-D17B04','Reservation details have been updated.','reservation','3','sent',NULL,'2026-10-18 14:23:49',NULL,NULL,'2026-10-18 14:23:49');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('15','5',NULL,'in_app','reservation_updated','Reservation RSV-20261018-D17B04','Reservation RSV-20261018-D17B04 was updated.','reservation','3','sent',NULL,'2026-10-18 14:23:49',NULL,NULL,'2026-10-18 14:23:49');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('16','9','3','sms','reservation_updated','Reservation RSV-20261018-D17B04','Reservation RSV-20261018-D17B04 details were updated. Pickup 2026-10-19 16:04:00.','reservation','3','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 14:23:49');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('17','3','3','in_app','reservation_status','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 is now confirmed. Booking invoice INV-20261018-E6C8A1 is ready. Pay it before the rental agreement and vehicle pickup.','reservation','1','read',NULL,'2026-10-18 14:38:15','2026-10-20 07:08:09',NULL,'2026-10-18 14:38:15');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('18','5',NULL,'in_app','reservation_status','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 is now confirmed. Booking invoice INV-20261018-E6C8A1 is ready. Pay it before the rental agreement and vehicle pickup.','reservation','1','sent',NULL,'2026-10-18 14:38:15',NULL,NULL,'2026-10-18 14:38:15');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('19','9','3','sms','reservation_status','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 is now confirmed. Booking invoice INV-20261018-E6C8A1 is ready. Pay it before the rental agreement and vehicle pickup.','reservation','1','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-18 14:38:15');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('96','3','3','in_app','reservation_updated','Reservation RSV-20261018-42B343','Reservation details have been updated.','reservation','1','sent',NULL,'2026-10-20 07:08:44',NULL,NULL,'2026-10-20 07:08:44');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('97','5',NULL,'in_app','reservation_updated','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 was updated.','reservation','1','sent',NULL,'2026-10-20 07:08:44',NULL,NULL,'2026-10-20 07:08:44');
INSERT INTO `notifications` (`notification_id`,`user_id`,`customer_id`,`channel`,`notification_type`,`subject`,`message`,`related_entity_type`,`related_entity_id`,`status`,`scheduled_at`,`sent_at`,`read_at`,`error_message`,`created_at`) VALUES ('98','9','3','sms','reservation_updated','Reservation RSV-20261018-42B343','Reservation RSV-20261018-42B343 details were updated. Pickup 2026-10-20 13:43:00.','reservation','1','failed',NULL,NULL,NULL,'HDEV SMS credentials are not configured in the server environment.','2026-10-20 07:08:44');
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `permission_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('78','rentals.view','View rental records');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('79','transactions.view','View payment and refund records');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('80','maintenance.view','View maintenance records');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('101','transactions.approve','Approve or reject refund and financial adjustment requests');
INSERT INTO `permissions` (`permission_id`,`permission_key`,`description`) VALUES ('255','reservations.decide','Approve or reject reservations with a signed decision');
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
  `payment_status` enum('unpaid','pending','successful','failed','cancelled','refunded') NOT NULL DEFAULT 'unpaid',
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `customer_signature` mediumblob DEFAULT NULL,
  `customer_signature_user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_signature_captured_by` bigint(20) unsigned DEFAULT NULL,
  `customer_signed_at` datetime DEFAULT NULL,
  `approval_note` varchar(500) DEFAULT NULL,
  `approver_signature` mediumblob DEFAULT NULL,
  `decision_by` bigint(20) unsigned DEFAULT NULL,
  `decision_at` datetime DEFAULT NULL,
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
  KEY `idx_res_payment_status` (`payment_status`,`updated_at`),
  KEY `fk_res_customer_signature_user` (`customer_signature_user_id`),
  KEY `fk_res_customer_signature_captor` (`customer_signature_captured_by`),
  KEY `fk_res_decision_user` (`decision_by`),
  CONSTRAINT `fk_res_confirmer` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_res_customer_signature_captor` FOREIGN KEY (`customer_signature_captured_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_customer_signature_user` FOREIGN KEY (`customer_signature_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_decision_user` FOREIGN KEY (`decision_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_res_period` CHECK (`return_datetime` > `pickup_datetime`),
  CONSTRAINT `chk_res_amounts` CHECK (`agreed_daily_rate` >= 0 and `estimated_days` > 0 and `estimated_amount` >= 0 and `discount_amount` >= 0 and `tax_amount` >= 0 and `security_deposit` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `reservations` (`reservation_id`,`reservation_code`,`customer_id`,`vehicle_id`,`pickup_datetime`,`return_datetime`,`pickup_location`,`return_location`,`agreed_daily_rate`,`estimated_days`,`estimated_amount`,`discount_amount`,`tax_amount`,`security_deposit`,`status`,`payment_status`,`customer_notes`,`internal_notes`,`customer_signature`,`customer_signature_user_id`,`customer_signature_captured_by`,`customer_signed_at`,`approval_note`,`approver_signature`,`decision_by`,`decision_at`,`cancellation_reason`,`created_by`,`confirmed_by`,`confirmed_at`,`cancelled_at`,`created_at`,`updated_at`) VALUES ('1','RSV-20261018-42B343','3','2','2026-10-20 13:43:00','2026-10-21 13:43:00','kirehe','kamonyo','38000.00','1.00','38000.00','0.00','0.00','0.00','pending','unpaid','i like it','','‰PNG\r\n\Z\n\0\0\0\rIHDR\0\0X\0\0\0´\0\0\0HR\0\0\0IDATxìİ{Ç}ØñîÙ½;<\n0îvA»=F\"i)%…‰-Å)Ø‰ã˜MwGE¬˜ªØI)UIœä¯<ş	+UNå/Wª\\•JJ²*’[â=\0º,ÙÑÛUN\"¿d›¤H÷\0)Äî@ˆÃİN·{vogzövo_³³³»ßÅìN÷tOwÏgPµ¿ê™s/@\0@\0H°\"å¤1@\0hhÁ À\ZìóÇè@\0@ X	<)	(h@ Xı³¥çln^efæÕÉ“Oÿ‡X:¤@\0X\rÿ$¯`jvŞ5“ÎÎÎë©Ü¼Úk„\'N<ó‡ÙÜœRHéygLı—½êS†\0 €\0Ñ	8Ñ5EK½ÈÌ|bÓœ,GÈJ/¬¦*yûóØìÙÍ»[RÚ›I#€\0ƒ\0Ç0Î@ŒrÄyğÁg®JgßD-C&óì·j·yhá-3mµ«nm=ò €\0 Ğ;¬ŞÙFÖò=ã[ÙV\ZËÎÌ©q­	ÁÌ•à…@cJ@\0`õœ¸»¼û­šµ™™sËõœğ5AÚ1œ‘A\0@\0H°\"åŒ¶±ÌôÜv¨E-BQÒæa÷®wÓ»täîóhêJ«ºëÊĞ¾¡vÛÍP@\0öØıÅ¼gu\nã)™õ\'İ»v~¿ºûóµW½(*•Ş~±òÃàRáÆ…%ÎµG\Z@`è’t@|é&élXc™š]p­¬Ğæ%DzÌŞfWfÂJ¸nê½b~I^yõ…;2(5e^ÜÚ• €\0ôNÀé]Ó´Ü…À¤#tèÜW—WhY¯M=éb~Yn\\øÊA¯|ffîÓŞÚß•ßğÓ$@\0† €@T¡/ñ¨\Z¥î²¹ù¢İ‚²äåÍÉª`i\\…/ÿ½£ßğêWßÅ7–~®šf\0 €\0½0ßÙ½ï„Úá™ªõüâXfú¬2Ñ•Y‚¶´º_ŞuS)ªÖ2—«IÖ1Ğ €\0À®/go#ïş	dÊ¿â(­ÕvfvNÉ”lÜ^q-<sµ³Y˜K†Õ¤Rn	^ €\0 «@Â¬X=™ÕürPJ‘–&Jª3ØPU-?;û²š1ëâÉ›5+@\0ˆQ€\0+Fìf]İ÷§¾oG•ú»g®ÊÛë†WB”„ü©ryõãë_¹šd\0 Ğ¡\0»!Ğ¦\0V›`½¬®Jé¿ŞmûfÆËÑ¸ÿª[MöG\0@ 3¬ÎÜz³—²QÃ··¶ÿŸ]¦eøV+»ÌJ7˜ç²jD zA\0FJ€\0+!§{jv^5\ZJiÿş/İzı…ÛånI×\rìR‰;ö>¤@\0@ ¬xœ›öbNÄ®Ù+/X*ä—äµ¿ô¬¨ma«´]»ÉËKk,-ôw¼m¼@\0@ ^ó½o‡ô¶[ ûàBİ™¦¢	®ªµ{lîWÓŞúàxéYom¿3-\\\nòZ¼¹¶ò‰ O\n@\0èh±¾\0V}—Ø¶~taEŒë}áµğf®ìmùúóvş‹_]´ó^ZjuÂ[WŞ²²â@\0b ÀŠ<èĞ®&îê3Á“2×ùå]ÑQZ8)SÚlñ÷SB›–šU§ú/À@`°útVËÁÕfMpeÆân¾}Ë¬v-æDùÁÓ®Â:¤×ëlf €\0Ä `¾·cè….B‡9óByæªNÈ´qù[‡B•[ÌLúÔg…uƒ{quyRŒÈ‹ÃD\0@ iX1Ÿ‘òÌÕVêêu«Jºá£\ZB±X‹©ôö3Õ6ëW‹X#€\0 €@1ôBerpU{ÏU¹¤ò±~q¹ñ}VÚš\nE[•}•û+)!ÌåAÁ@\0ú\'ÀVLö‡zæÛåË‚ûÓ‹L‰T™Šf1Û¬E\ní×ĞRn[E$@\0Á`Ô°\0V\'Ï®è­ŸŞ««»¥¿µW¹=™JR‰]–A\rGËM5@\0è“\0VáÍ|ò;µÁ•‰Ìbu¬µ¸qñ›¡‰Z¥å¤½ƒ«d©¼qçcòÄ“ŸÙI–WWWŸÿå£.Àñ#€\0ôI€\0«‡ğå™+Ç=mwa%-J[5OnO5¼¹İÛ÷èÑ…?öÖÕ÷äD)ÔæØØø¯TËDÉ­ Œ €\0Ä!@€µ—reåàªæ² ‰¢t1¿äÈô¸Cº×EaõùÆ7·›\né#â#få/?üá¹ÿïgLÂMIë	îf €\0ôU€\0«ü“\'Ÿzs×eA%ôº	®vºn˜jiÊÉİó<I¥¬€Ínz§7V €\0C%ÀÁ$_`Ï/îä?™#L¥‡GæêâÚRÙ:33ú…_©ä†òáı*9mİÀ^ÙRû©ƒ0-ÍDYm9y@\0ˆS ü¥g‡ÃŞW67¯ícTBéBş\\à,E	!®]<7aV{.RXV¨õÊnZVÖåO©¶Êk>@`Š@\0Ş\n_ü½íg$ZÏææ”°‚-´XÏ¯„ŒM¨ä×ĞZÕ	—êPÙµ¤i´¦Š´\",íˆBM1Y@\0ˆY ôåsßCÕ]vúŒ+¤‚\'stÅüÔ¬üeræ¬ò3^Bg¾í­š¾ıV½¶ÂM”÷•A–vÕ7ÊÛzüAó €\0 ĞX€\0«±MË%Ç™{E¤ËR‹ô¸~Uˆç¾o7’r+T¢¸ö?ş]ŞJº´]ó÷\nOŸ>)DĞì•ÕsÿJğB\0@`4sÔVP˜1\rØ@ûHiK~PXAp•ºòƒå‡…õÚâ™×¬¬w¯ÎT”]ÃO?â§Lâî¡K_7+™úÑÑÿîg¼V…Øò¤@\0@ X]ªgs/ÿY0d.ái¡Î…nd÷º¸w|+ç­«ïb~iWj™½><ûäŸÛùÛ/ıùãvŞIIÿ2¤¶ƒ<»i@ Uê!€@$X]0fsóª6¦)®VÇ°«Yª©w•7Ø0¦ÒA Vg/©åáê®²NyµŒ5 €\0Ä\'@€Õ¡õäôÓ›&d’öî…ÌÑhç«éÌÌÓ¡G\'(©ÜjY³uJÊ  åï¦´Hû™:¿0ôËHÄ%@? €\0¬şLM=ş…TJ…_uW¦¯‹ÿû?¿V·9GA©°şÚÊ˜Yµ´¨:A•½£Ê?‡æêd(³ë‘F\0@\0øü/çøºlÒÓ\0;‡<\Z¦RêÆk_>\ZÚÎa’ªÜ‰.Ş3ìk\"¨ÚšÚL£U·™Š7«iÖ €\0 Ğ?¬6íËµöÑBèÂÚJpŸ”Uæ%§rgB“Pî;ÿÔÛŞêÛ¾¯JÛ™LPµ“ÂÑî?C@ R\ZC ¬6´¦fÂõv-æÜÔîš·ã¤¤Y•Œ‰/üÍr¦Õooå5á%‚·U^Rú\\P@\n@\0ú%@€Õ¢üäÉ§Ş4Á’ïåE:êÆ{_Ük÷C|ìBU\0i¯jÊ¶7ÇÜĞ¦Ó§°óë^øov4É`4 €ÀèøÃèrgGêŒ¥Û{–Jbk}ıkŸ¶·Õ¦÷ï»ï1{[q-üw	í²zé#Gæòöö·ôü¸Ï\\¾ï¿y/äwƒ<)@\0è—\0VòÙÙ9ëÏ)›´Ğ×/.M˜Ô‹4¯=+4)t&SA-‚t%åH÷\'*)ó¡ÙÀ‚\0 €\0ı Àj\"?•›SÂú¥ĞZ\Z=LT¯#ï_¸ä„(é’iÇŞÒ<Ö:8?å	ªğ>J»Çª[ˆ¯ª¬@\0b »:ÁxÂQß”Í)95³P…ÕåĞ¥ÂFFcûô½A™×VÏ7ü¥aP/œ²c*-­»ÙwªIáì;/SjWßÙÊ\n@\0ú!@€Õ@ığ£¿#¤\nŠn—ô÷Lõ·Ì»é\"u0íe’ºéu*H´!ë”›+•şø´áàëÔg$T€a!€ÀĞ	`58¥ãwÕ\"ˆo¼èEİº¸ü“¢…WæÔ/Ü¶vnI¶}ypW7&šªİf0“ò=?C@\0ú*@€U‡?;;¯LÀâ—hÜlä—üÙ\"¿ AB¦Ç÷W‹¼©«kC*§ZÖÎÚkg¯úJª+{•y‡‡\0 €@¢°jNG6wÆ›m’ÕÍ^`Slá¦öjıµ¿¿IxMìlî|%…öÆjÀn8]rZºtj€ €\0ôD `õ¤éÁkôÈCÿèšÁ“×½#(Şï?ñÖ­¾Z=‹J—d‡÷FúY»Ïíë©Wì|m:ª@®¶]ò €\0 Ğ¾\0VÕìô/ıò˜.ıX5ë­İíÒñİÏÿ//İê[;jÌ®[¼¸ÊÛe{¥¼ÿ±¯Úå×¯?ÿ!;O\ZA ÀÚ9sÙË7?gfvrB(åªKçï÷7´šp¤ÕŒ¶¯âµÚB¹ŞØ¾í–ïù*ïÀ €\0$F€\0ËœŠLn^Ù¿ú3a‘^_;×v€3yêé-Óœ¿ÈwPŞ/h!!…¨u¦µĞU†\\€ÃC\0è‡ÀÈX“³ó®	e¤ßÁMíåİSieıZP‹«Wı–+´ñ¡u0$+ÙFTE\0@\0~	Œt€uü‘¹WÌ4U``fŠÔ÷¾hŸŒ6ÓAT$¤i­Í½­êÒ¼ªYÓhWmUÛa\0 €\0ñÁE<ı%¨—ç>RÚ’\rh[Ş^_ÿÚ§CÛZÌdO-”ìª%¥Šv¾«´&¾êÊ@\0áàˆ.0²V6÷ÒŸ™™!ÿô(íêÂë‹÷øÚLè”Y^[[9Öf\r«+-ˆ°\ZêP€\0 €@òBAAò†×›esŞMívx¥Åúê¹®,¤2­	‰‚L×©m§Ôá³´ºîš^è¡@WAEÇÕ³¦\'§ŸŞ4¡	QÈ/OvÓ¡w£¼½¿z\'õ‡v¾İôá‡æì}nä_xÄÎWÓö]^fîzu;k@\0è¯ÀHXSS!•R6ùí’şÉ_3ï—”!Çõââßé¸1³ãØÃ7+{ÉÛ?m…‰NJ®øÛãIĞ €\04\rêÍfçğgíƒ1×İÔ­‹Ë?ioë(m:JtGº“luÇtúthÖíJ~y©n=6\"€\0 0RÉ8Ø‘	°²¹9e“+­õF~)eoë$yh!Ôîúş÷ız\'í„÷±\"¶··»2ù/ÃûˆíšÃ.+\0\0\0IDAT<Y@\0è“ÀHXS3g\\!¥µx1Ëúêr$Ç.µ\nµ+^üü¿îö\\jŒµÑã´ÌLYíeÄn»e@ ,Àt/IÑı0z×ÂäÉ§ŞtœTè8‹ù{&¢Â¯„àò 0/+¼29Ó¬÷Yóv\\«nŠªßj{¬@\0@ ;PàÑ]SIÜû—~:5–>nÌİ.]âóß±·uš>6;ÿRußò¬Xşøgªù¨ÖJ…®@úÍj\'õcÕŒ	ÈÜjšu €À¨u€•}çÛö	VÊU—Îßooë&­u0}U™ÇúõÏvÓ^½}¥H×°„Ø_­/µ¼[M³F\0@\0ş$2ÀŠ‚¥rS{%ì©´§õúÚ¹T%ÁçÉ\'~-¯„¹\'Bmu6ujî_Ø;¸7Ü?µóAZUÓZKUÅ`\0 €@†2ÀÊæÎ–„”Vt¥½‡‰Fz¬ÙññcŸ¿b~1üw\ríÂ6ÒZ:¿fW¿vmå\'ì|5­«	³VBı…Y± €\0ôV€ÖhY Ò £å^{Xñğ£¿#¤ÌT™Hä}òÖ¼Kû—~•Æ_«¬ºûL9:İJVô(œéÿØÊ>ÔA\0@\0x†.À\ZßÔOØtJ¦î¼öÚÿùOö¶nÓS³¿ü»5m¨š|çY)d³Íİ?\rê˜Ù¹¿òr\'…@‚\Z 0\"C`™ÀCI+<q•«×ó_9õ¹täÇí6<°ÔjÏ?3ùfm’şyÓÍã1Á@\0âğ¿¨ãí6úŞÍ~òu-MèQmZ±±v®7Ç§í0®ÚaDkm¾Ó”‰´‚Le[åS{Æ¬~JM>@\0@ ½	@úp J¸\'L@bõœ~ÃÊD–Ìüï…\ZÓ:âgPG¡µG½jG^ñsZ`ù$@\0ú#@¯µC`Måæİ ,&âĞº°úåk6Š¼»gŸİNau¹¥›Òí}öL[\"eıÓ£¶Ååjf.ív5Í\Z@\0’!Pÿ<ckmO<1g.†£˜æïÖ€‰Ìâ—ôxöÈº^èw)DñâòKBŠr`¥…üºUDT€a#€Àp	„“A<´ì«‹ö¸Õv©şu5»R‡éc¹…M{W­eÄ—íÖ…Ø£q=±9ş~G«ó‹óá½È!€\0 €@¿:À:6}ö]3“Ì(i!Ö/±®’zÜn²¸ºè?MİŞYZ;æˆê·öÆ¿õö[«+ß¯_:Š[9f@\0’#0Ğ–N9÷T)½HÄ¹­>WÍ÷bm\"9³TZ6ı™¥’êóCúÅGí¶Ò·RåË€ö6Ò €\0 |?ÀJşPÃ#œÊÍ….jWë·ŞZùgáZÑå&gŸŞ²[ÛVNÉÎG‘ŞØ~7ô|­«W?öLíÒ €\0Ä+0Ö}÷ı¬#¥?›ä‘­_èİí^û)¡B¿|{íùĞåB¯N·ï÷n=nãW¾\ZÎ“C\0FN€F` 2ÀR¥õ§bŒûv:Ki~@g®\ršÅôñ2‘Ş\Z‹¸IšC\0@\0>\\€59;ï\n{òJ]¸ôeÿ^¬^fg¶CíªÍp>TØyFË´ÄuŞ\n{\"P#@@ vA°››¥âäM#Vß÷³•\\/?uğËDÓiqíw\'zÑ›4×={Ñ.m\"€\0 €@¼~°o·õ–ÉÍ]¶§x´+”¿ñ­ÎZky/¯bĞ­™1ó6ôä­EĞOO: Q@\0ˆC``¬cí©5i^Šë–‚™¥  ÒÔdn!ôkÁ;ºôN¤´ĞØ±ÜÜ#-T£\n €ÀH	p°I˜\0K¹éirìÎøì|¯Ò)i]4Ü¼pşˆYu½Lš»™ÍÍ«ììœ®¾…ÁªlŸ×ZÊ—³³ózj6ühŠ®A €\0 Ğ§\'­FÜhfú¬’öÅ3s™îòåß=”3â.ë6§Í«nA33^P5¯SiyÈ„Sæ¨Ìb¢ü5/¯,Øä)½@+c³©éOŞJH!€@\'ìƒ\0ôJÀéUÃ‘¶›r‚HCQX]ŠeÜÙÙ×>â»ø˜o5™+es•™*éˆàXZm ¦	6¥“ry3\\™™ğ%Ìšªd@\0@ Núl«Ko¶ÆHÌõ²PĞÓVcmWÖaŸÂ¯~¯Õ&œX¸d e.j™’)a¢\"!ì#æe¢Ea¿Í&kÑ®Òş[íÕ´ŠMR\néèTvölŒ&¦[!\0 €\0õÂD½\Z}Ü6•›ß”&†¨Á\\ÅµÅĞÕ«e=X‡n,W^çÍ;™1cVÙÙy}hB?(„=zQ~•ƒ$ÓÖÖ½YÈ/Kûmb¨rïC+¡‹Vÿ_rŠùÇş¾«•.·áUòßÒ¹gæ©U?K@\0QHÀ±\':À2ƒ=o*uK..³ìÌÜKv_ë«rÒÎÛé£ÓOU/®:V@h×ñÒfšIóKÒ»ÄùöååıŞ¶ğ[úY33å§ƒÄ¿ûÆÆª	ºL%å=¢¢Z\"ÅA\'=SÍ±F\0@\0ş\n˜¦¿hÔ{&7§Do˜jnOÿ˜³é ¼8föigKeÆhñúNÖ_y¿êË˜Ùªt*İà 0QÖê†úı‚	Š6ò­?VBéĞÁû}V×Ö¼¶ÜÊĞv6š™3µ“d…\0t#À¾ Ğ¥@b,i^ö±òçbëÂÛ}›¨Å,fËéÓdgçÊ—\0M0£Q3HS¥º¨ÍRÉªÖóËÎúúÊÏW··º6áU(xª·_™>c&ÊêÕf €\0Ä%àÄÕQ›ı˜ø\"ØC•T%À	6õ4•ÕîÀ^@•}sòGÂU¢ÁËDDÚª¼÷úÎwõ‡›¥¦¹Y›ŞxØÊ\n‘J9§Ÿ¼ÚF&~zD\0iDXSÓó¡§§¯_\\IÅ|–¤İ_J¤;_M{yëÛzûU/¨*æ£{|Ä¦(ÿ¼°ÚUãõ7¿ùª¾«B^÷¤ÆN5Ş@\0èµ@İÀ¡×6kßqt_Æudzá}™™¹æ³eZk¡mPIóvn­¾Ejv€­”K}µ•j^â+cfH&Öór•wfv¾ùqTªò‰\0 €\0ô%iz2˜@òWĞ´~V~X¾¯j<¥oHëæöP³ÚL))éz3U…Õe§°úüx¨<âÌ;yy²&‹fLö“²¤²ı©‹\0 ĞŠ\0uhM qÖáÜÂ»öĞïîß°óQ¤§fÏºŞL½ûªv~Ø0q…P• jIöø\\5÷l-š®Û;Z}sÿïù{˜#ºï¾O]óó$@\0@ 6ÄXãRô^ñÎËK?çç»HdfÏ˜ Êûs5óÚ7OeB½T®tÛy´ÂŞ­5-M7­Ñ¤B±ø›ÛUJ·\"ùÃÔv›¤èV€ı@\0QH\\€e¢³Tè•Ô&Äª¤;ø<5i‚*o–Ê{K‘2A•ß´¨})íjáı³\nÖ/ÄöÔx¯×iï£Û·óÍö<àn;b@\0h(¨\0ëÃ>óïC#-™p!´¡Yæ3ONÍUÙÙòLÕ…”	ªöÚÃ\\ƒó.ÿò.®¯s„4ÿvv0QŠYv2ñ¬ZìoïÁ(iÅ½«PŠ\0 €\0=pzÜ~[Í_¹åüª½ƒ“–N%Xò¦zïyÍÍëÌlåÍ];o.ÿIÄIÂ™XM{oázA•ô‚ªË—¼:GO¹ë­«ï’–&şªæbY_Œ¢—ûß¾ßn\'sâì¶\'\0 €@Oh4$¨\0+=._®(™x©áÚÔ6Åf)×¨|˜mÖâÅT®Pª°º,‹Ş;.eûÉôX*t“ùõÕÅPŞ¯Ø»D4Ğw¿~¼Ã˜S÷x{w´Œ\0 €\0‰\n°ŞzuiÚ\\\'3Kç\'¦¼³™ª*¹%×›¥*®.9ù–TêÅiåMf)\'òÃŞ,•¡KY/ì¬”ñ‰\0‰`0 0D‰\n°<WïÁfm³ˆ½ß¦Ô„RZ+¡´Ú.)í]7ûK3[å\\»p¾å_åeš=	İrËŒ!îe\"ªeT\rÑ €\0t$àt´Ww23ON!¿,›½M0åW—õüŠ³~é|ªxá·v44%}´‰«ùÅ}µÓİNm=Xt¯®ìCÚé½öŠ2@\0„øEBÆÓ—a˜ ÊšôÑ&Û—aü ª^¥uUĞNGÕ>í €\0 €ÀŞv€µwÍ!-ÍNŸu¥´nûö¦•È¤=ke§ò`4 €\0(0ò–H9!ƒâ¥ß?Ğ§óøHŸú¥[@ áÁƒ7ü.Güş›¡˜î	qA\0@\0ÎF:ÀÊ–6	Ø´(¬.´G`AjØ8@\0xF; ÖİàñºÓ €\0±ÀÈXGsá\'§+g?3Äÿ84@\0ˆ\\`d¬´Ô¡‘ÖCùÈ¥›7Ø¯ÇC45@\0’)À¨+0ªVíßçKBpóÃ¨ş—H\\ú=‚BğB\0@\08F2ÀÊÎÎ‡.niy3ì&}|°IyëÅV¸¨”´r­7AMFD€ÃD\0z\"0’–‘4“<æsgy{uñğNr8VÖÑiG`\rÇYå(@\0H`ä¬£\'¼cŸ¥ûö§qìaD–¾÷á_|YWÅFş§>!zù¢m@\0Ø%0rVzblÂVX_İ÷·ìü §\'6ï—\ZËsWÿö÷ı˜? €\0í\nô»şÈXÜ¿€Vypûÿş³-	ËìAÿè§ş¶o5-SÖô•¤­îM=@\0ˆB`¤¬ÌìYe£©’åí²~§İëï}§“1H¡ƒ°ªAvÒû €ÀÈ\0€\0İŒT€%…Bˆ‹Ë)³JäRrÆ:;7V|¥tbãÇDš3(@\0¢èìK<ªŞûØÂ,}@½®­9Ò\rƒõª×İfíµ}W”êÖac,t‚\0 0º#`iQydÇóöûÉí»ÿ×Y¬’²ıS39;ús?7.¯Œïî„- €\0 Ğkö¿Å{=\"¿ıèÅüŠSÈ/É¢yñ\\â®Ÿy_õ¨¥ÕdËë”¶®¶¼@\0@ j‘\n°¢Æ‹¼=mMa‰\",)ı¨ÌÌÕÙñšà…\0 ‘\0Í Ğ‚\0VHqU‘:ˆ‰‚Tk½g¦Ï„fäîïìo¶6j!€ÈÖœ\0\0ÒIDAT\0 €@U€\0«*1àk™\n~!ég7_ùÒÃ~Hx82@`è°tŠµõXÿZ_ã;ğÀ|QXW5Ïg¼@\0è§\0V?õkú6‘7ùT³µN¶fÓ¡ıbÒßdZX_Kîó½üq’@\0@`ˆ°trqç=œééÏÌÚù=ÒÁ„—}#×;P„\0 €@Ô´`}O^[¹ÇÄ5÷õ¿°óõÒ™Ù3¡g_½{GnÔ«Ç6@\0ˆO€\0+>ë¶{\ZOïß×l\'©ÿj¡Å{——2Íö¡’*À¸@`Xü/ça9 a:Ç	kUï¸xà+Âª¢]zTC½}Ø†\0 €\0½ Àê½q[=hó\nv°¢§`£ŸÚŞ7ñ”Ÿ1‰õK)³\Zé…ƒG\0@ 	XI8öB1Upïº]ÅOKiWĞşv €\0 ĞWš\0«¯c¡s# ·K-JÙÜ\\ÍÍí‚›Û €\0I ÀJÂY°Æ°~`ì~++2Ós%;ï§¥;nn÷eH €À0\npL˜@èKzÀÆ>œÃ}eñjèÀRÂ	åMf*7º™İLe…ò¦\n €\0ôQ`×—wÇB×;Ú¼v’BŠĞ}VåÍæ¤Érbçc#ÏÍí;¬\ZP‚\0 £€ù®±7ºjI@¹Î¶]q2·à_&œÊÍ)a…W®ÒfË®M\Z@\0ú-@€Õêˆ±ŞÆÅÅ	»»”Ô©©™3næÄÙmi^A™kËé O\n@\0’ @€•„³Pg®p•½ÙqRœpÒÖä•¸{G‡fºìú¤@\0FC€£L¦\0V2Ï‹ØÈŸK	­?²Á”Ü¸¼2Ğá3,@\0FZ€\0+Á§¿°ºìl	é\nLÙÃ4q—Ö7|ÛŞF\Z:`?@ z¬èM#mñíübº°º$KJ(3£åòK²h¯bñ‹7Òh@\0\" ÀŠŒ²·\r][[JV“yC{oœÖ@\0<¬Á;gŒ@\0šôµV_ùé@\0†Q€\0kÏ*Ç„\0D!@ Ğ±\0VÇtìˆ\0 €\0Ô ÀªïÂV¢ \r@\0 À\ZÑÏa#€\0 €@ï’`õî¸i@\0è™\0VÏhi@`X8.š	`5¢@\0hS€\0«M0ª#€@´\0·\0ÖpŸ_@\0ú @€Õô(º¤\r@\0H®\0VrÏ\r#C\0@`Ğï\0Ö+@\0@ *¬¨$iˆB€6@`(°†â4r €\0 $¬$\rÆ…\0m €\0 Ğw¬¾Ÿ€\0 €\0Ã&°;À\Z¶#äx@\0@\0˜°b§;@\0ÎØA À\Z¤³ÅX@\0@` °â41H¢ \r@\0¸°â’¦@\0¬6N5U@\0@\0Vş\n\0\0ÿÿ—2f§\0\0\0IDAT\0ëÆÒüg8\0\0\0\0IEND®B`‚',NULL,'3','2026-10-20 07:08:44',NULL,NULL,NULL,NULL,NULL,'9',NULL,NULL,NULL,'2026-10-18 13:43:37','2026-10-20 07:08:44');
INSERT INTO `reservations` (`reservation_id`,`reservation_code`,`customer_id`,`vehicle_id`,`pickup_datetime`,`return_datetime`,`pickup_location`,`return_location`,`agreed_daily_rate`,`estimated_days`,`estimated_amount`,`discount_amount`,`tax_amount`,`security_deposit`,`status`,`payment_status`,`customer_notes`,`internal_notes`,`customer_signature`,`customer_signature_user_id`,`customer_signature_captured_by`,`customer_signed_at`,`approval_note`,`approver_signature`,`decision_by`,`decision_at`,`cancellation_reason`,`created_by`,`confirmed_by`,`confirmed_at`,`cancelled_at`,`created_at`,`updated_at`) VALUES ('2','RSV-20261018-F50593','3','2','2026-10-20 13:43:00','2026-10-21 13:43:00','kirehe','kamonyo','38000.00','1.00','38000.00','0.00','0.00','0.00','pending','unpaid','i like it',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9',NULL,NULL,NULL,'2026-10-18 13:43:37','2026-10-18 13:43:37');
INSERT INTO `reservations` (`reservation_id`,`reservation_code`,`customer_id`,`vehicle_id`,`pickup_datetime`,`return_datetime`,`pickup_location`,`return_location`,`agreed_daily_rate`,`estimated_days`,`estimated_amount`,`discount_amount`,`tax_amount`,`security_deposit`,`status`,`payment_status`,`customer_notes`,`internal_notes`,`customer_signature`,`customer_signature_user_id`,`customer_signature_captured_by`,`customer_signed_at`,`approval_note`,`approver_signature`,`decision_by`,`decision_at`,`cancellation_reason`,`created_by`,`confirmed_by`,`confirmed_at`,`cancelled_at`,`created_at`,`updated_at`) VALUES ('3','RSV-20261018-D17B04','3','2','2026-10-19 16:04:00','2026-10-28 04:04:00','kirehe','kamonyo','38000.00','9.00','304000.00','0.00','0.00','0.00','pending','unpaid','yguhijop[\'','fgdssd',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9',NULL,NULL,NULL,'2026-10-18 14:03:03','2026-10-18 14:23:49');
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
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','14');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','15');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','17');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','18');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','37');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','38');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','78');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','79');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('1','80');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','3');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','5');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','8');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','12');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','13');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','14');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','15');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','37');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','38');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('2','101');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','3');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','5');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','7');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','8');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('3','255');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','4');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','10');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','11');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('4','15');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('5','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('5','12');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','2');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','6');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','9');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('6','78');
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES ('7','38');
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('1','1','System','Administrator','admin@crms.local','+250785354935','$2y$10$aLpQE.Knwug2SZFkbZspgO.Ngw6o7DJykpvzm6CQjhe3KROytft0W','active','2026-10-20 07:24:40','2026-10-15 21:43:57','2026-10-20 07:24:40');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('3','2','DUKUZUMUREMYI','Elias','manager@crms.local','0785354935','$2y$10$7nNQRjSmFBC14bMy9FglT.U0R2Jr6IdA.Umxsf0n6dbwzTDuN3pK2','active','2026-10-20 06:57:39','2026-10-18 11:45:44','2026-10-20 06:57:39');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('4','4','DUKUZUMUREMYI','Elias','accountant@crms.local','0785354935','$2y$10$KHBlpkAZ9lyh4iVfv2PAde3gsoaqLORethX0E.2NwPpZf4fMkSttK','active','2026-10-18 14:39:52','2026-10-18 11:57:05','2026-10-18 14:39:52');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('5','3','DUKUZUMUREMYI','Elias','rental@crms.local','0785354935','$2y$10$ASRc/W18Sae2kigYc4Valu8nJzokusfYWVVEYH6nJ1YB82Fw/ZDGa','active','2026-10-18 14:35:40','2026-10-18 12:29:46','2026-10-18 14:35:40');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('6','5','DUKUZUMUREMYI','Elias','inspector@crms.local','0785354935','$2y$10$uVqPNVq/vx2iYRGdyrMGvuD57XrFwB9Sw9IWeL5jZIe8clVErDgf.','active','2026-10-18 13:10:47','2026-10-18 12:56:10','2026-10-18 13:10:47');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('7','7','DUKUZUMUREMYI','Elias','driver@crms.local','0785354935','$2y$10$yaInwvf3lY0T55TkQdVZx.TwNc1zhOOiPzzZlcm3YitodLY3lYPBq','active','2026-10-18 13:12:42','2026-10-18 13:12:32','2026-10-18 13:12:42');
INSERT INTO `users` (`user_id`,`role_id`,`first_name`,`last_name`,`email`,`phone`,`password_hash`,`status`,`last_login_at`,`created_at`,`updated_at`) VALUES ('9','6','DUKUZUMUREMYI','Elias','urhe665@gmail.com','0785354935','$2y$10$4aWR8H2VymSt0fGVikUmrOI7A8phOZl2ZXU09JwmRsAmTMG87uCwO','active','2026-10-18 14:38:27','2026-10-18 13:27:21','2026-10-18 14:38:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `vehicles` (`vehicle_id`,`category_id`,`registration_no`,`vin`,`make`,`model`,`manufacture_year`,`colour`,`transmission`,`fuel_type`,`daily_rate`,`weekly_rate`,`monthly_rate`,`current_mileage`,`fuel_level_percent`,`image_path`,`status`,`insurance_expiry_date`,`inspection_expiry_date`,`description`,`created_by`,`created_at`,`updated_at`) VALUES ('1','2','RAD-001-A',NULL,'Toyota','Corolla','2020','White','automatic','petrol','35000.00','210000.00','700000.00','12000',NULL,'public/img/fleet/default.svg','available',NULL,NULL,NULL,'1','2026-10-15 21:43:57','2026-10-20 07:12:49');
INSERT INTO `vehicles` (`vehicle_id`,`category_id`,`registration_no`,`vin`,`make`,`model`,`manufacture_year`,`colour`,`transmission`,`fuel_type`,`daily_rate`,`weekly_rate`,`monthly_rate`,`current_mileage`,`fuel_level_percent`,`image_path`,`status`,`insurance_expiry_date`,`inspection_expiry_date`,`description`,`created_by`,`created_at`,`updated_at`) VALUES ('2','2','RAD-002-B',NULL,'Honda','Civic','2021','Silver','automatic','petrol','38000.00','228000.00','760000.00','8000',NULL,'public/img/fleet/default.svg','available',NULL,NULL,NULL,'1','2026-10-15 21:43:57','2026-10-20 07:08:44');
INSERT INTO `vehicles` (`vehicle_id`,`category_id`,`registration_no`,`vin`,`make`,`model`,`manufacture_year`,`colour`,`transmission`,`fuel_type`,`daily_rate`,`weekly_rate`,`monthly_rate`,`current_mileage`,`fuel_level_percent`,`image_path`,`status`,`insurance_expiry_date`,`inspection_expiry_date`,`description`,`created_by`,`created_at`,`updated_at`) VALUES ('3','3','RAD-100-S',NULL,'Toyota','RAV4','2022','Black','automatic','petrol','60000.00','360000.00','1200000.00','5000',NULL,'public/img/fleet/default.svg','available',NULL,NULL,NULL,'1','2026-10-15 21:43:57','2026-10-15 21:43:57');
DROP TABLE IF EXISTS `vw_invoice_balances`;
;
INSERT INTO `vw_invoice_balances` (`invoice_id`,`invoice_number`,`customer_id`,`total_amount`,`net_paid`,`outstanding_balance`,`status`) VALUES ('1','INV-20261018-E6C8A1','3','38000.00','0.00','38000.00','void');
INSERT INTO `vw_invoice_balances` (`invoice_id`,`invoice_number`,`customer_id`,`total_amount`,`net_paid`,`outstanding_balance`,`status`) VALUES ('2','INV-20261018-592B84','3','304000.00','0.00','304000.00','void');
INSERT INTO `vw_invoice_balances` (`invoice_id`,`invoice_number`,`customer_id`,`total_amount`,`net_paid`,`outstanding_balance`,`status`) VALUES ('3','INV-20261018-179F1A','3','304000.00','0.00','304000.00','draft');
INSERT INTO `vw_invoice_balances` (`invoice_id`,`invoice_number`,`customer_id`,`total_amount`,`net_paid`,`outstanding_balance`,`status`) VALUES ('12','INV-20261020-E3960D','3','38000.00','0.00','38000.00','draft');
SET FOREIGN_KEY_CHECKS=1;
