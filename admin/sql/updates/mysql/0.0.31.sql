-- What the AI features cost. Every provider call that reports tokens gets a row: which feature
-- made it, what it was about, the tokens, and the cost estimated from the connector's rates at
-- the time of the call. It is a log for watching the spend; the provider's bill is entered as an
-- expense by hand.

CREATE TABLE IF NOT EXISTS `#__mothership_ai_usage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL COMMENT 'the connector, e.g. claude',
  `model` VARCHAR(100) NULL DEFAULT NULL,
  `feature` VARCHAR(50) NOT NULL DEFAULT 'other' COMMENT 'expense.read, connection.test, other',
  `resource_type` VARCHAR(30) NULL DEFAULT NULL COMMENT 'what the call was about, e.g. invoice',
  `resource_id` INT(11) NULL DEFAULT NULL,
  `client_id` INT(11) NULL DEFAULT NULL,
  `account_id` INT(11) NULL DEFAULT NULL,
  `project_id` INT(11) NULL DEFAULT NULL,
  `input_tokens` INT(11) NOT NULL DEFAULT 0,
  `output_tokens` INT(11) NOT NULL DEFAULT 0,
  `cost` DECIMAL(12,6) NULL DEFAULT NULL COMMENT 'estimated USD; NULL when the model has no rate',
  `created` DATETIME NOT NULL,
  `created_by` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ai_usage_created` (`created`),
  KEY `idx_ai_usage_client` (`client_id`),
  KEY `idx_ai_usage_resource` (`resource_type`, `resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
