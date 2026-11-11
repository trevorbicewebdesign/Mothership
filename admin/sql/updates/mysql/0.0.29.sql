-- Expenses: money the business spent.
--
-- An expense can be assigned to a client, to one of its accounts and to one of that account's
-- projects, or left unassigned (general overhead). All three are optional, so they carry no
-- foreign keys and deleting a client leaves its expenses in place.
--
-- billable marks an expense that can be billed back to the client and reimbursed that it has
-- been. invoiced_invoice_id is reserved for the invoice it was billed on; nothing sets it yet.
-- payment_account is free text: which account or card the expense was paid from.

CREATE TABLE IF NOT EXISTS `#__mothership_expenses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(50) NULL DEFAULT NULL,
  `item` VARCHAR(255) NULL DEFAULT NULL,
  `merchant` VARCHAR(255) NULL DEFAULT NULL,
  `client_id` INT(11) NULL DEFAULT NULL,
  `account_id` INT(11) NULL DEFAULT NULL,
  `project_id` INT(11) NULL DEFAULT NULL,
  `expense_date` DATE NULL DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `billable` TINYINT(1) NOT NULL DEFAULT 0,
  `reimbursed` TINYINT(1) NOT NULL DEFAULT 0,
  `invoiced_invoice_id` INT(11) NULL DEFAULT NULL,
  `transaction_id` VARCHAR(100) NULL DEFAULT NULL,
  `payment_account` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Which account or card it was paid from',
  `description` TEXT NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `checked_out` INT(11) NULL DEFAULT NULL,
  `checked_out_time` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_expense_client` (`client_id`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_expense_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
