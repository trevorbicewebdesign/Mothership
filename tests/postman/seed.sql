-- The records the Postman collection (postman/collections/Mothership API) asserts on.
-- One of each resource, under ids no other test uses, so it can be loaded again.
--
--   docker compose exec -T db mysql -uroot -proot local < tests/postman/seed.sql

DELETE FROM `jos_mothership_invoice_payment` WHERE `payment_id` = 9001 OR `invoice_id` = 9001;
DELETE FROM `jos_mothership_invoice_items` WHERE `invoice_id` = 9001;
DELETE FROM `jos_mothership_proposal_items` WHERE `proposal_id` = 9001;
DELETE FROM `jos_mothership_payments` WHERE `id` = 9001;
DELETE FROM `jos_mothership_invoices` WHERE `id` = 9001;
DELETE FROM `jos_mothership_proposals` WHERE `id` = 9001;
DELETE FROM `jos_mothership_domains` WHERE `id` = 9001;
DELETE FROM `jos_mothership_projects` WHERE `id` = 9001;
DELETE FROM `jos_mothership_accounts` WHERE `id` = 9001;
DELETE FROM `jos_mothership_clients` WHERE `id` = 9001;

INSERT INTO `jos_mothership_clients` (`id`, `name`, `email`, `phone`, `address_1`, `address_2`, `city`, `state`, `zip`, `tax_id`, `default_rate`, `created`)
VALUES (9001, 'Postman Test Client', 'client@example.com', '555-0100', '1 Test Street', NULL, 'Testville', 'CA', '90001', '00-0000000', 100.00, '2026-01-02 03:04:05');

INSERT INTO `jos_mothership_accounts` (`id`, `client_id`, `name`, `rate`, `created`)
VALUES (9001, 9001, 'Postman Test Account', 125.00, '2026-01-02 03:04:05');

INSERT INTO `jos_mothership_projects` (`id`, `client_id`, `account_id`, `name`, `description`, `type`, `status`, `metadata`, `created`)
VALUES (9001, 9001, 9001, 'Postman Test Project', 'A project for the API tests', 'website', 'active', '{"cms": "joomla"}', '2026-01-02 03:04:05');

INSERT INTO `jos_mothership_domains` (`id`, `name`, `client_id`, `account_id`, `status`, `epp_status`, `registrar`, `dns_provider`, `ns1`, `auto_renew`, `created`)
VALUES (9001, 'postman-test.example.com', 9001, 9001, 'active', '["clientTransferProhibited"]', 'Example Registrar', 'Example DNS', 'ns1.example.com', 1, '2026-01-02 03:04:05');

INSERT INTO `jos_mothership_proposals` (`id`, `name`, `client_id`, `account_id`, `project_id`, `type`, `number`, `total_low`, `total`, `rate`, `status`, `created`)
VALUES (9001, 'Postman Test Proposal', 9001, 9001, 9001, 'hourly', 'P-9001', 200.00, 250.00, 125.00, 2, '2026-01-02 03:04:05');

INSERT INTO `jos_mothership_proposal_items` (`proposal_id`, `name`, `description`, `type`, `quantity`, `quantity_low`, `rate`, `subtotal`, `subtotal_low`, `ordering`)
VALUES (9001, 'Build', 'Build the thing', 'hourly', 2.00, 1.60, 125.00, 250.00, 200.00, 1);

INSERT INTO `jos_mothership_invoices` (`id`, `number`, `client_id`, `account_id`, `project_id`, `rate`, `title`, `status`, `total`, `due_date`, `locked`, `created`)
VALUES (9001, '9001', 9001, 9001, 9001, 125.00, 'Postman Test Invoice', 2, 250.00, '2026-02-01', 1, '2026-01-02 03:04:05');

INSERT INTO `jos_mothership_invoice_items` (`invoice_id`, `name`, `description`, `hours`, `minutes`, `quantity`, `rate`, `subtotal`, `ordering`)
VALUES (9001, 'Build', 'Build the thing', 2, 0, 2.00, 125.00, 250.00, 1);

INSERT INTO `jos_mothership_payments` (`id`, `client_id`, `account_id`, `amount`, `payment_date`, `payment_method`, `transaction_id`, `status`, `created_at`)
VALUES (9001, 9001, 9001, 100.00, '2026-01-10 00:00:00', 'zelle', 'POSTMAN-9001', 2, '2026-01-10 00:00:00');

INSERT INTO `jos_mothership_invoice_payment` (`invoice_id`, `payment_id`, `applied_amount`)
VALUES (9001, 9001, 100.00);
