-- Convert storage engine for clients to InnoDB
ALTER TABLE `#__mothership_clients` ENGINE = InnoDB;

-- Remove the unique index on name (if exists)
ALTER TABLE `#__mothership_clients` DROP INDEX `name`;

-- Modify default_rate to use DECIMAL(10,2)
ALTER TABLE `#__mothership_clients`
    MODIFY `default_rate` DECIMAL(10,2) DEFAULT NULL;

-- Allow address_2 to be NULL
ALTER TABLE `#__mothership_clients`
    MODIFY `address_2` VARCHAR(255) DEFAULT NULL;

-- Convert accounts table to InnoDB and enforce foreign key on client_id
ALTER TABLE `#__mothership_accounts` ENGINE = InnoDB;
ALTER TABLE `#__mothership_accounts`
    MODIFY `client_id` INT(10) NOT NULL;
ALTER TABLE `#__mothership_accounts`
    MODIFY `rate` DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE `#__mothership_accounts`
    ADD CONSTRAINT `fk_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE;

-- Convert invoices table to InnoDB and add foreign key constraints
ALTER TABLE `#__mothership_invoices` ENGINE = InnoDB;
ALTER TABLE `#__mothership_invoices`
    MODIFY `rate` DECIMAL(10,2) DEFAULT NULL,
    MODIFY `total` DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE `#__mothership_invoices`
    ADD CONSTRAINT `fk_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_invoice_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts`(`id`) ON DELETE SET NULL;

-- Convert invoice items table to InnoDB and update numeric fields
ALTER TABLE `#__mothership_invoice_items` ENGINE = InnoDB;
ALTER TABLE `#__mothership_invoice_items`
    MODIFY `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    MODIFY `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    MODIFY `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE `#__mothership_invoice_items`
    ADD CONSTRAINT `fk_invoice_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices`(`id`) ON DELETE CASCADE;

-- Convert payments table to InnoDB and adjust schema
ALTER TABLE `#__mothership_payments` ENGINE = InnoDB;
ALTER TABLE `#__mothership_payments`
    MODIFY `amount` DECIMAL(10,2) NOT NULL,
    MODIFY `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE `#__mothership_payments`
    ADD CONSTRAINT `fk_payment_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE CASCADE;

-- Create new invoice payment join table if it doesn't exist
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_payment` (
    `payment_id` INT NOT NULL,
    `invoice_id` INT NOT NULL,
    `applied_amount` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`payment_id`, `invoice_id`),
    CONSTRAINT `fk_ip_payment` FOREIGN KEY (`payment_id`) REFERENCES `#__mothership_payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ip_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
