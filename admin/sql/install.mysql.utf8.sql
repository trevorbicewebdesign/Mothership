-- Clients Table
CREATE TABLE IF NOT EXISTS `#__mothership_clients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `address_1` VARCHAR(255) NOT NULL,
  `address_2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(32) NOT NULL,
  `state` VARCHAR(32) NOT NULL,
  `zip` VARCHAR(32) NOT NULL,
  `tax_id` VARCHAR(30) NOT NULL,
  `default_rate` DECIMAL(10,2) DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Accounts Table
CREATE TABLE IF NOT EXISTS `#__mothership_accounts` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `client_id` INT(10) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `rate` DECIMAL(10,2) DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_client` (`client_id`),
  KEY `idx_name` (`name`(100)),
  CONSTRAINT `fk_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;

-- Invoices Table
CREATE TABLE IF NOT EXISTS `#__mothership_invoices` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(50) DEFAULT NULL,
  `client_id` INT(10) DEFAULT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `rate` DECIMAL(10,2) DEFAULT NULL,
  `status` INT(11) DEFAULT NULL,
  `total` DECIMAL(10,2) DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `sent_date` DATE DEFAULT NULL,
  `paid_date` DATE DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  KEY `fk_client` (`client_id`),
  KEY `fk_account` (`account_id`),
  KEY `idx_number` (`number`(50)),
  CONSTRAINT `fk_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invoice_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts`(`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;

-- Invoice Items Table
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_items` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `invoice_id` INT(10) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `hours` INT(11) NOT NULL DEFAULT 0,
  `minutes` INT(11) NOT NULL DEFAULT 0,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  KEY `fk_invoice` (`invoice_id`),
  KEY `idx_name` (`name`(191)),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_invoice_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;

-- Payments Table
CREATE TABLE IF NOT EXISTS `#__mothership_payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `client_id` INT NOT NULL,
  `account_id` INT DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATETIME NOT NULL,
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fee_passed_on` TINYINT(1) NOT NULL DEFAULT 0,
  `payment_method` VARCHAR(50) NOT NULL, 
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `status` INT NOT NULL DEFAULT 0,
  `processed_date` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payment_client` (`client_id`),
  KEY `idx_transaction_id` (`transaction_id`(100)),
  CONSTRAINT `fk_payment_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoice Payment Mapping Table
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_payment` (
  `payment_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `applied_amount` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`payment_id`, `invoice_id`),
  CONSTRAINT `fk_ip_payment` FOREIGN KEY (`payment_id`) REFERENCES `#__mothership_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ip_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table
CREATE TABLE IF NOT EXISTS `#__mothership_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `client_id` INT(11) NOT NULL,
  `role` ENUM('owner', 'employee', 'administrator') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_user_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
