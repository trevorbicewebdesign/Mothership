-- disable foreign keys so we can drop/recreate safely
SET FOREIGN_KEY_CHECKS = 0;

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
  `owner_user_id` INT(11) DEFAULT NULL,
  `invoice_pdf_template` VARCHAR(100) NOT NULL DEFAULT 'default' COLLATE 'utf8mb4_unicode_ci',
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
  KEY `fk_accounts_client` (`client_id`),
  KEY `idx_name` (`name`(100)),
  CONSTRAINT `fk_accounts_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;

-- Domains Table
CREATE TABLE IF NOT EXISTS `#__mothership_domains` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `client_id` INT(10) NOT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
  `epp_status` JSON DEFAULT NULL,
  `registrar` VARCHAR(255) DEFAULT NULL,
  `reseller` VARCHAR(255) DEFAULT NULL,
  `dns_provider` VARCHAR(255) DEFAULT NULL,
  `ns1` VARCHAR(255) NULL DEFAULT NULL,
  `ns2` VARCHAR(255) NULL DEFAULT NULL,
  `ns3` VARCHAR(255) NULL DEFAULT NULL,
  `ns4` VARCHAR(255) NULL DEFAULT NULL,
  `purchase_date` DATETIME DEFAULT NULL,
  `expiration_date` DATETIME DEFAULT NULL,
  `auto_renew` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `checked_out` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_domains_client_mship` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_domains_account_mship` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts`(`id`) ON DELETE SET NULL,
  KEY `idx_name` (`name`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;

-- Proposals Table
CREATE TABLE IF NOT EXISTS `#__mothership_proposals` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,

  -- Optional but required by prepareTable()
  `name` VARCHAR(255) NULL COLLATE 'utf8mb4_unicode_ci',

  -- Relationship fields from form XML
  `client_id` INT(10) DEFAULT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `project_id` INT(10) DEFAULT NULL,

  -- Proposal-level type
  `type` VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',

  -- Visible proposal-level fields
  `number` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `total_low` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  -- Business logic
  `status` INT(11) NOT NULL DEFAULT 1,
  `expires` DATE DEFAULT NULL,
  `locked` TINYINT(1) NOT NULL DEFAULT 0,

  -- Text fields
  `summary` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',
  `notes` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',

  -- Joomla core fields
  `state` TINYINT(3) NOT NULL DEFAULT 0,
  `created` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP),
  `created_by` INT(11) DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT(11) DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `version` INT(11) NOT NULL DEFAULT 1,

  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_number` (`number`),
  
  CONSTRAINT `fk_proposal_client_mship`
    FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposal_account_mship`
    FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposal_project_mship`
    FOREIGN KEY (`project_id`) REFERENCES `#__mothership_projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Proposal Items Table
CREATE TABLE IF NOT EXISTS `#__mothership_proposal_items` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `proposal_id` INT(10) NOT NULL,

  `name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `description` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',

  `type` ENUM('hourly','fixed')
        NOT NULL DEFAULT 'hourly'
        COLLATE 'utf8mb4_unicode_ci',

  `time` VARCHAR(10) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
  `time_low` VARCHAR(10) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',

  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity_low` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal_low` DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  `ordering` INT(11) NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  KEY `idx_proposal_id` (`proposal_id`),

  CONSTRAINT `fk_proposal_items_proposal_mship`
    FOREIGN KEY (`proposal_id`) REFERENCES `#__mothership_proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Invoices Table
CREATE TABLE IF NOT EXISTS `#__mothership_invoices` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `client_id` INT(10) NULL DEFAULT NULL,
  `account_id` INT(10) NULL DEFAULT NULL,
  `project_id` INT(10) NULL DEFAULT NULL,
  `rate` DECIMAL(10,2) NULL DEFAULT NULL,
  `title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `status` INT(11) NULL DEFAULT NULL,
  `total` DECIMAL(10,2) NULL DEFAULT NULL,
  `due_date` DATE NULL DEFAULT NULL,
  `sent_date` DATE NULL DEFAULT NULL,
  `paid_date` DATE NULL DEFAULT NULL,
  `locked` TINYINT(1) NOT NULL DEFAULT 0,
  `summary` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `invoices_pdf_template` VARCHAR(100) NULL DEFAULT 'default' COLLATE 'utf8mb4_unicode_ci',
  `created` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP),
  `created_by` INT(11) NULL DEFAULT NULL,
  `checked_out_time` DATETIME NULL DEFAULT NULL,
  `checked_out` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_client` (`client_id`) USING BTREE,
  INDEX `fk_account` (`account_id`) USING BTREE,
  INDEX `idx_number` (`number`) USING BTREE,
  CONSTRAINT `fk_invoice_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
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
  `ordering` INT(11) NOT NULL DEFAULT 0,
  KEY `fk_invoice_items_invoice` (`invoice_id`),
  KEY `idx_name` (`name`(191)),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices`(`id`) ON DELETE CASCADE
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
  `locked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payments_client` (`client_id`),
  KEY `idx_transaction_id` (`transaction_id`(100)),
  CONSTRAINT `fk_payments_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoice Payment Mapping Table
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_payment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `payment_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `applied_amount` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_invoice_payment_payment` FOREIGN KEY (`payment_id`) REFERENCES `#__mothership_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projects Table
CREATE TABLE IF NOT EXISTS `#__mothership_projects` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `client_id` INT(10) NOT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `type` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `metadata` JSON DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_projects_client` (`client_id`),
  KEY `idx_name` (`name`(100)),
  CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_projects_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;

-- Users Table
CREATE TABLE IF NOT EXISTS `#__mothership_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `client_id` INT(11) NOT NULL,
  `role` ENUM('owner', 'employee', 'administrator') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_users_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs Table
CREATE TABLE IF NOT EXISTS `#__mothership_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NULL DEFAULT NULL,
  `account_id` INT(11) NULL DEFAULT NULL,
  `object_type` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `object_id` INT(11) NULL DEFAULT NULL,
  `action` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `meta` JSON NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `details` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `user_id` INT(11) NULL DEFAULT NULL,
  `created` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP),
  `notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
