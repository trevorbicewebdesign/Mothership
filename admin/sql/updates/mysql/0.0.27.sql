-- Email log: one row for every email the component tries to send, whatever became of it.
--
-- Until now a message that the mail server refused, or that never left because mail is switched
-- off, vanished without a trace: EmailService returned false and nothing read the result. This
-- table is the record. status is one of
--   sent      the mail server accepted it (not the same as delivered)
--   failed    it did not go, and error says why
--   skipped   it was deliberately not sent, e.g. "save without emailing the client"
--   pending   the send began and the site stopped before recording the result
-- object_type/object_id tie it to the invoice, payment or proposal it
-- was about, so each of those screens can list its own email. body_html keeps what was (or would
-- have been) sent, so a failed or skipped message can be read and resent as it was.
CREATE TABLE IF NOT EXISTS `#__mothership_emails` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NULL DEFAULT NULL,
  `account_id` INT(11) NULL DEFAULT NULL,
  `object_type` VARCHAR(50) NULL DEFAULT NULL,
  `object_id` INT(11) NULL DEFAULT NULL,
  `template` VARCHAR(100) NULL DEFAULT NULL COMMENT 'email layout, e.g. invoice.user-opened',
  `recipient` VARCHAR(255) NOT NULL,
  `cc` VARCHAR(255) NULL DEFAULT NULL,
  `bcc` VARCHAR(255) NULL DEFAULT NULL,
  `reply_to` VARCHAR(255) NULL DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` MEDIUMTEXT NULL,
  `message_id` VARCHAR(255) NULL DEFAULT NULL,
  `status` VARCHAR(10) NOT NULL DEFAULT 'pending',
  `error` TEXT NULL COMMENT 'why it failed, or why it was skipped',
  `resend_of` INT(11) NULL DEFAULT NULL COMMENT 'the log entry this one repeats',
  `user_id` INT(11) NULL DEFAULT NULL COMMENT 'who caused it; null when the site did on its own',
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_status` (`status`),
  KEY `idx_email_object` (`object_type`, `object_id`),
  KEY `idx_email_client` (`client_id`),
  KEY `idx_email_recipient` (`recipient`),
  KEY `idx_email_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
