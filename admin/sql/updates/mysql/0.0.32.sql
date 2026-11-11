-- Attachments: a file kept with a record. context says what kind of record and resource_id
-- which one. The first use is the receipt behind an expense (context 'expense'), kept by
-- "New from PDF". The files themselves sit above the web root, in mothership/expenses/receipts,
-- and are only served through the expense controller, to administrators.

CREATE TABLE IF NOT EXISTS `#__mothership_attachments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `context` VARCHAR(50) NOT NULL COMMENT 'what kind of record the file belongs to, e.g. expense',
  `resource_id` INT(11) NOT NULL,
  `filename` VARCHAR(255) NOT NULL COMMENT 'the name it is stored under: the hash of its bytes and its extension',
  `original_name` VARCHAR(255) NULL DEFAULT NULL,
  `mime` VARCHAR(100) NULL DEFAULT NULL,
  `size` INT(11) NULL DEFAULT NULL,
  `uploaded_by` INT(11) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ctx` (`context`, `resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
