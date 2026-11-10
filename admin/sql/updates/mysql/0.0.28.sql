-- Portal users: who besides a client's owner can use its portal.
--
-- #__mothership_users has been in the schema since the start, with a role of owner, employee or
-- administrator, but nothing read it: the portal only ever showed a client to its owner
-- (clients.owner_user_id). From here on a row is a grant. Someone is invited to a client, gets
-- a one-time link to set a password when they have no login yet, and then sees that client in
-- the portal alongside the owner.
--
-- role is one of
--   full_admin     everything the owner has. The only level offered, and the only one the
--                  portal acts on, in this release
--   account_admin  reserved: everything, on chosen accounts
--   billing_admin  reserved: invoices and payments, on chosen accounts
-- all_accounts and #__mothership_user_accounts belong to the two reserved levels.
--
-- accepted is when the person set their password (or, for someone who already had a login, when
-- they were given access). While it is NULL the invite is pending, and invite_expires says
-- until when its link works. Only a hash of the link's token is kept.

-- The unique key below needs one row per person and client; keep the oldest.
DELETE `d` FROM `#__mothership_users` AS `d`
  INNER JOIN `#__mothership_users` AS `k`
    ON `k`.`user_id` = `d`.`user_id` AND `k`.`client_id` = `d`.`client_id` AND `k`.`id` < `d`.`id`;

ALTER TABLE `#__mothership_users`
  MODIFY `role` ENUM('owner','employee','administrator','full_admin','account_admin','billing_admin') NOT NULL;

UPDATE `#__mothership_users` SET `role` = 'full_admin' WHERE `role` IN ('owner', 'administrator');
UPDATE `#__mothership_users` SET `role` = 'account_admin' WHERE `role` = 'employee';

ALTER TABLE `#__mothership_users`
  MODIFY `role` ENUM('full_admin','account_admin','billing_admin') NOT NULL,
  ADD COLUMN `all_accounts` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Account/Billing Administrator on every account, including ones added later' AFTER `role`,
  ADD COLUMN `created` DATETIME NULL DEFAULT NULL AFTER `all_accounts`,
  ADD COLUMN `created_by` INT(11) NULL DEFAULT NULL AFTER `created`,
  ADD COLUMN `invited_as` INT(11) NULL DEFAULT NULL COMMENT 'Named as the inviter, e.g. the owner when staff invite on their behalf' AFTER `created_by`,
  ADD COLUMN `invite_token_hash` CHAR(64) NULL DEFAULT NULL AFTER `invited_as`,
  ADD COLUMN `invite_expires` DATETIME NULL DEFAULT NULL AFTER `invite_token_hash`,
  ADD COLUMN `accepted` DATETIME NULL DEFAULT NULL AFTER `invite_expires`,
  ADD COLUMN `invite_sent_at` DATETIME NULL DEFAULT NULL COMMENT 'when the invite email last left the site' AFTER `accepted`,
  ADD COLUMN `invite_error` VARCHAR(255) NULL DEFAULT NULL COMMENT 'why the last invite email failed, if it did' AFTER `invite_sent_at`,
  ADD UNIQUE KEY `idx_user_client` (`user_id`, `client_id`),
  ADD KEY `idx_invite_token` (`invite_token_hash`);

-- Rows that were already here were never invites, so they are not pending.
UPDATE `#__mothership_users` SET `accepted` = UTC_TIMESTAMP() WHERE `accepted` IS NULL;

CREATE TABLE IF NOT EXISTS `#__mothership_user_accounts` (
  `user_access_id` INT(11) UNSIGNED NOT NULL,
  `account_id` INT(10) NOT NULL,
  PRIMARY KEY (`user_access_id`, `account_id`),
  KEY `idx_account_id` (`account_id`),
  CONSTRAINT `fk_user_accounts_access` FOREIGN KEY (`user_access_id`) REFERENCES `#__mothership_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_accounts_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
