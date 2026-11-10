![Run Mothership Tests](https://github.com/trevorbicewebdesign/mothership/actions/workflows/codeception.tests.yml/badge.svg)
# Mothership
Mothership is a friendly, open-source Joomla extension built for solo developers and small businesses. It started as a personal project designed to simplify everyday challenges like proposals, invoicing, payments, domain tracking, and logs. Over time, it evolved into a tool that handles these essential functions in one place—making life easier not just for me, but for anyone juggling the many hats of a web development operation. Support tickets, time tracking, expenses, and taxes are planned but are not part of the current release.

At its core, Mothership organizes your business around eight main objects:

- **Clients**
- **Accounts**
- **Projects**
- **Domains**
- **Proposals**
- **Invoices**
- **Payments**
- **Logs**

The model is flexible enough to accommodate real-world complexities. For example, a single client might manage multiple accounts—whether these represent different parts of the business, subdomains, or entirely separate brands. Each account can then have its own projects. While projects currently focus on websites, the architecture is designed to eventually support other types of work, like graphic design or any other service you might offer.

One of the standout features in the initial release is the projects module. Often, a big part of launching a new website involves researching the domain—finding out details like hosting information, email configurations, and even identifying the underlying CMS or technologies in use. Mothership’s scanning feature does exactly that, gathering vital domain data and making it easy to have informed discussions with your clients. And because these tools are available from the front end, your clients can also use them to better understand their projects.

In short, Mothership is built to streamline your workflow and let you focus on what really matters—delivering great work and growing your business.

# Requirements

- Joomla 5.x or 6.x (verified against 5.4.10 and 6.2.0). Joomla 6 support starts with 0.0.26; 0.0.25 and earlier are Joomla 5 only.
- PHP 8.1 or newer for the component; Joomla 6 itself requires PHP 8.3 or newer
- MySQL 8 or MariaDB

# Back End

## Creating an Invoice

# Front End

## Paying an Invoice

### Steps:
1) Visit the `View All Invoices Page`
2) Click `Pay` next to the invoice you wish to settle
3) User is taken to the Select Payment Page, where each enabled payment method is listed with its fee and payment instructions. An invoice that already has a pending payment cannot start a second one.
4) User selects a payment method and clicks `Pay Now`
5) A payment is created with the status `Pending` and the administrator is emailed
6) User is taken to the `Thank You` page where details about the pending transaction are listed
7) User sends the payment (for example mails the check or sends the Zelle transfer)
8) Administrator opens the payment and clicks `Confirm Payment`, which sets the payment status to `Completed`
9) User receives an email letting them know the process completed, and the invoice is closed once it is fully paid

<div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between;">
  <img src="https://github.com/user-attachments/assets/25a09847-0867-4a2e-a665-db72e5038fe4" width="24%" />
  <img src="https://github.com/user-attachments/assets/3e46c7d4-94cf-46ec-9864-1e21616f7504" width="24%" />
  <img src="https://github.com/user-attachments/assets/53685d26-93b3-4e41-83c2-3ece6cdaff40" width="24%" />
  <img src="https://github.com/user-attachments/assets/74392d8c-1216-40fa-ac77-fd250ae30daf" width="24%" />
</div>

---

## Clients
The **Clients** object represents the individuals or organizations you work with.

- **ID**: A unique identifier for the client.
- **Name**: The name of the client.
- **Email**: The client's email address.
- **Phone**: The client's phone number.
- **Address**: Includes address lines, city, state, and zip code.
- **Tax ID**: The client's tax identification number.
- **Default Rate**: The default billing rate for the client.
- **Owner User ID**: The ID of the user who owns the client record.
- **Invoice PDF Template**: The invoice PDF template used for this client's invoices. Defaults to `default`.
- **Created**: The timestamp when the client record was created.
- **Created By**: The ID of the user who created the client record.
- **Checked Out Time**: The timestamp when the client record was last checked out.
- **Checked Out**: The ID of the user who last checked out the client record.

### Clients Table
```
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
```

## Accounts
The **Accounts** object represents the different accounts managed by a client.

- **ID**: A unique identifier for the account.
- **Client ID**: The ID of the client to whom the account belongs.
- **Name**: The name of the account.
- **Rate**: The billing rate for the account.
- **Created**: The timestamp when the account record was created.
- **Created By**: The ID of the user who created the account record.
- **Checked Out Time**: The timestamp when the account record was last checked out.
- **Checked Out**: The ID of the user who last checked out the account record.

### Accounts Table
```
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
```

## Domains
The **Domains** object represents the domains associated with client accounts. 

- **ID**: A unique identifier for the domain.
- **Name**: The name of the domain (e.g., example.com).
- **Client Id**: The ID of the client who owns the domain.
- **Account Id**: The ID of the account associated with the domain.
- **Status**: The current status of the domain (e.g., active, expired).
- **EPP Status**: The EPP status codes reported for the domain (e.g., `ok`, `clientTransferProhibited`), stored as a JSON list.
- **Registrar**: The registrar where the domain is registered.
- **Reseller**: The reseller through whom the domain was purchased, if applicable.
- **DNS Provider**: The provider managing the domain's DNS settings.
- **NS1**: The primary nameserver for the domain.
- **NS2**: The secondary nameserver for the domain.
- **NS3**: An optional tertiary nameserver for the domain.
- **NS4**: An optional quaternary nameserver for the domain.
- **Purchase Date**: The date when the domain was purchased.
- **Expiration Date**: The date when the domain is set to expire.
- **Auto Renew**: Whether the domain is set to renew automatically.
- **Notes**: Free-form notes about the domain.
- **Created**: The timestamp when the domain record was created.
- **Modified**: The timestamp when the domain record was last modified.
- **Checked Out**: The ID of the user who last checked out the domain record.
- **Checked Out Time**: The timestamp when the domain record was last checked out.

### Domains Table
```
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
```

### Domain Status Levels
- **Active**: The domain is currently active and operational.
- **Inactive**: The domain is not currently in use or has been deactivated.
- **Pending**: The domain is awaiting activation or completion of a required process.
- **Suspended**: The domain has been temporarily disabled due to policy violations or other issues.

### Domain WHOIS Scan
The domain edit screen can run a WHOIS lookup (`domain.whoisScan`, backed by `DomainHelper::scanDomain()` and the `io-developer/php-whois` library). The scan fills in the registrar, name servers, DNS provider, EPP status, and the purchase and expiration dates, and records a `scanned` entry in the logs.

## Proposals

The **Proposals** object represents proposed scopes of work generated for clients. A proposal may represent a fixed price, a range estimate, or a rate-based engagement, and can optionally be associated with a client account and project.

Each proposal includes the following attributes:

- **ID**: Unique identifier for the proposal.
- **Name**: Optional human-readable name or title for the proposal.
- **Number**: Proposal reference number (used for external identification and indexing).
- **Type**: Proposal type (e.g. fixed, range, hourly). Stored as a short string for flexibility.
- **Client ID**: The client to whom the proposal belongs.
- **Account ID**: Optional account associated with the proposal (for clients with multiple accounts).
- **Project ID**: Optional project this proposal is related to.
- **Rate**: Billing rate associated with the proposal (used primarily for hourly or hybrid proposals).
- **Total (Low)**: The lower bound of the proposal total, used for range-based proposals.
- **Total**: The primary or upper total amount of the proposal.
- **Status**: Numeric proposal status (e.g. draft, sent, approved, rejected). Status values are managed at the application level.
- **Expires**: Optional expiration date for the proposal.
- **Locked**: When set, the proposal becomes read-only and cannot be modified.
- **Summary**: High-level summary or scope description of the proposal.
- **Notes**: Internal notes related to the proposal (not intended for client visibility).
- **State**: Publish state flag (used for soft deletion and visibility control).
- **Created**: Timestamp when the proposal was created.
- **Created By**: User ID of the creator.
- **Modified**: Timestamp of the most recent modification.
- **Modified By**: User ID of the last editor.
- **Checked Out**: User ID of the user currently editing the proposal.
- **Checked Out Time**: Timestamp when the proposal was checked out for editing.
- **Version**: Incrementing version number used for optimistic locking and change tracking.

### Proposal Tables
```
CREATE TABLE IF NOT EXISTS `#__mothership_proposals` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL COLLATE 'utf8mb4_unicode_ci',
  `client_id` INT(10) DEFAULT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `project_id` INT(10) DEFAULT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
  `number` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `total_low` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` INT(11) NOT NULL DEFAULT 1,
  `expires` DATE DEFAULT NULL,
  `locked` TINYINT(1) NOT NULL DEFAULT 0,
  `summary` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',
  `notes` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',
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
  CONSTRAINT `fk_proposal_client_mship` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposal_account_mship` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposal_project_mship` FOREIGN KEY (`project_id`) REFERENCES `#__mothership_projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
```

### Proposal Lifecycle Status Levels
Proposal status is stored as an integer from 1 (`Draft`) to 6 (`Expired`), in the order listed below. Setting a proposal to `Pending` fires the `onMothershipProposalPending` event. A proposal can be `Locked` and `Unlocked` from the proposal edit screen.

- **Draft**: The proposal is being created or edited and has not yet been finalized or shared with the client.
- **Pending**: The proposal has been finalized and sent to the client for review and is awaiting a response.
- **Approved**: The proposal has been accepted by the client and is eligible for conversion into an invoice or project.
- **Declined**: The proposal has been reviewed and explicitly rejected by the client.
- **Cancelled**: The proposal was withdrawn or invalidated before a final decision was made.
- **Expired**: The proposal was not approved within the allowed time window and is no longer valid.

## Proposal Items
The **Proposal Items** object represents the individual items listed on a proposal. Each proposal item has the following attributes:

- **ID**: A unique identifier for the proposal item.
- **Proposal ID**: The ID of the proposal to which the item belongs.
- **Name**: The name of the item.
- **Description**: A description of the item.
- **Type**: The type of proposal item (Hourly or Fixed). 
- **Time**: The estimated or actual time required for the item, typically expressed in hours or as a time string.
- **Time Low**: The lower bound estimate of time required for the item, useful for range-based proposals.
- **Quantity**: The number of units or amount of the item being proposed (e.g., hours, licenses, deliverables).
- **Quantity Low**: The minimum or lower bound quantity estimate for the item, used in range or estimate proposals.
- **Rate**: The billing rate for the item.
- **Subtotal**: The calculated cost for this item (quantity × rate), representing the main or upper estimate.
- **Subtotal Low**: The lower bound cost estimate for this item, based on the lower quantity or time.
- **Ordering**: The position or order in which the item appears on the proposal, used for sorting or display.


### Proposal Items Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_proposal_items` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `proposal_id` INT(10) NOT NULL,
  `name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `description` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `type` ENUM('hourly','fixed') NOT NULL DEFAULT 'hourly' COLLATE 'utf8mb4_unicode_ci',
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
  CONSTRAINT `fk_proposal_items_proposal_mship` FOREIGN KEY (`proposal_id`) REFERENCES `#__mothership_proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
```

## Invoices
The **Invoices** object represents the invoices generated for clients. Each invoice has the following attributes:

- **ID**: A unique identifier for the invoice.
- **Number**: The invoice number.
- **Client ID**: The ID of the client to whom the invoice belongs.
- **Account ID**: The ID of the account associated with the invoice.
- **Project ID**: The ID of the project associated with the invoice, if any.
- **Rate**: The billing rate for the invoice.
- **Title**: An optional title for the invoice.
- **Status**: The status of the invoice (e.g., draft, opened, canceled, closed).
- **Total**: The total amount of the invoice.
- **Due Date**: The date by which the invoice should be paid.
- **Sent Date**: The date the invoice was sent to the client.
- **Paid Date**: The date the invoice was paid.
- **Locked**: Setting this to true will make the invoice view only.
- **Summary**: A summary of the work covered by the invoice.
- **Notes**: Notes for the invoice.
- **Invoice PDF Template**: The PDF template used to render the invoice. Defaults to `default`.
- **Created**: The timestamp when the invoice was created.
- **Created By**: The ID of the user who created the invoice.
- **Checked Out Time**: The timestamp when the invoice record was last checked out.
- **Checked Out**: The ID of the user who last checked out the invoice record.

### Invoices Tables
```
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
```

### Invoice Lifecycle Status Levels
Invoices that are set from `Draft` to `Opened` will have their `Locked` status set to true. Opened invoices should not be modified. Invoices that are in a `Locked` state can be `Unlocked` if it is necessary to override for some reason.

- **Draft**: The invoice is being created and is not yet finalized.
- **Opened**: The invoice has been finalized and sent to the client and is awaiting payment.
- **Cancelled**: The invoice has been canceled and is no longer valid.
- **Closed**: The invoice has been paid and is considered complete.

### Invoice Payment Status Levels
- **Unpaid**: The invoice has been issued but no payment has been received yet.
- **Partially Paid**: A portion of the invoice amount has been paid, but the full balance is still outstanding.
- **Paid**: The invoice has been fully paid and no balance remains.

## Invoice Items
The **Invoice Items** object represents the individual items listed on an invoice. Each invoice item has the following attributes:

- **ID**: A unique identifier for the invoice item.
- **Invoice ID**: The ID of the invoice to which the item belongs.
- **Name**: The name of the item.
- **Description**: A description of the item.
- **Hours**: The number of hours worked for the item.
- **Minutes**: The number of minutes worked for the item.
- **Quantity**: The quantity of the item.
- **Rate**: The billing rate for the item.
- **Subtotal**: The subtotal amount for the item.
- **Ordering**: The order in which the item appears on the invoice.

### Invoice Items Table
```
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
```

> **Note:** Only invoices in **Draft** status can be deleted. Attempting to delete an invoice that is **Opened**, **Cancelled**, or **Closed** will be blocked to prevent accidental data loss. When a draft invoice is deleted, all associated `Invoice Payments` records are also removed automatically to maintain data integrity.

## Payments
The **Payments** object represents the payments made by clients. Each payment has the following attributes:

- **ID**: A unique identifier for the payment.
- **Client ID**: The ID of the client associated with the payment.
- **Account ID**: The ID of the account associated with the payment.
- **Amount**: The total amount of the payment.
- **Payment Date**: The date and time when the payment was made.
- **Fee Amount**: The transaction fee associated with the payment.
- **Fee Passed On**: Whether the transaction fee was passed on to the client.
- **Payment Method**: The payment plugin used for the payment (e.g., `paybycheck`, `zelle`).
- **Transaction ID**: The transaction ID of the payment.
- **Status**: The status of the payment.
- **Processed Date**: The date and time when the payment was completed.
- **Locked**: Setting this to true will make the payment view only.
- **Created By**: The ID of the user who created the payment.
- **Created At**: The timestamp when the payment record was created.
- **Updated At**: The timestamp when the payment record was last updated.
- **Checked Out Time**: The timestamp when the payment record was last checked out.
- **Checked Out**: The ID of the user who last checked out the payment record.

The invoices a payment applies to are stored in the Invoice Payments table.

### Payments Table
```
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
```

### Payment Status Levels
- **Pending** (1): The payment has been initiated but not yet completed.
- **Completed** (2): The payment has been confirmed and successfully processed.
- **Failed** (3): The payment attempt was unsuccessful.
- **Cancelled** (4): The payment was cancelled before completion.
- **Refunded** (5): The payment has been refunded to the client.

## Invoice Payments
The **Invoice Payments** object represents payments that are applied to specific invoices. Invoice payments have a many to many relationship with payments and invoices. One payment can go to multiple invoices or multiple payments can go to one invoice.

- **ID**: A unique identifier for the payment.
- **invoice_id**: The invoice that this payment will be applied to
- **payment_id**: The payment that is being applied to the invoice
- **applied_amount**: The amount of the payment that is applied to this invoice

### Invoice Payments Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_payment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `payment_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `applied_amount` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_invoice_payment_payment` FOREIGN KEY (`payment_id`) REFERENCES `#__mothership_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Note:** If a draft invoice is deleted, any `Invoice Payments` records linked to that invoice will also be deleted automatically. This helps keep your data consistent and prevents orphaned records.

## Projects

- **ID**: A unique identifier for the project.
- **Client ID**: The ID of the client associated with the project.
- **Account ID**: The ID of the account associated with the project.
- **Name**: The name of the project.
- **Description**: The description of the project.
- **Type**: The type of project, currently there are only `Websites`
- **Status**: The status of the project. Can be `active` or `inactive`
- **Metadata**: Json to store data related to different project types
- **Created**: The date the project was created
- **Created By**: The user that created the project
- **Checked Out**: The ID of the user who last checked out the project record.

### Projects Table
```
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
```

## Users
The **Users** object links Joomla users to clients and gives each user a role for that client.

- **ID**: A unique identifier for the record.
- **User ID**: The ID of the Joomla user.
- **Client ID**: The ID of the client the user belongs to.
- **Role**: The user's role for the client: `owner`, `employee`, or `administrator`.

### Users Table
```
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
```

## Logs 

### Logs Table

- **ID**: A unique identifier for the log entry.
- **Client Id**: The ID of the client the entry relates to.
- **Account Id**: The ID of the account the entry relates to.
- **Object Type**: The type of object the entry is about (e.g., invoice, payment, domain, project).
- **Object Id**: The ID of that object.
- **Action**: The action that was logged (e.g., `viewed`, `scanned`, `status_opened`, `status_closed`, `payment_status_changed`).
- **Meta**: JSON data with additional details about the action.
- **Description**: A human-readable description of the entry.
- **Details**: Additional details about the entry.
- **User Id**: The ID of the user who performed the action.
- **Created**: The timestamp when the entry was created.
- **Notes**: Notes added to the entry.

```
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
```

## Payment Flow

1. **`InvoiceController->payment()`**  
   The payment process begins when the `invoice.payment` task is called. If the invoice already has a pending payment, the user is sent back to the invoice with a warning.

2. **Payment Layout (`payment.php`)**  
   The `payment.php` layout is rendered by the invoice controller (located alongside the default layout).  
   - Information about installed and enabled payment plugins is collected.  
   - The user selects a payment method.  
   - Any applicable fees and pre-payment instructions are shown to the user at this stage.

3. **`InvoiceController->processPayment()`**  
   After selecting a payment method, the user clicks the **Pay Now** button.  
   - A new record is created in both the `#__mothership_payments` and `#__mothership_invoice_payment` tables.  
   - The administrator is emailed the `payment.admin-pending` template and the initiation is logged.  
   - The selected plugin’s `initiate()` method is called, which begins processing the payment.  
   - Since payment methods may behave differently, the plugin is responsible for controlling the next steps.

4. **`PaymentPluginClass->initiate($payment, $invoice)`**  
   The plugin’s `initiate()` method receives both the `Payment` and `Invoice` objects.  
   - This allows it to carry out any required processing logic.  
   - For example, the **Pay by Check** plugin immediately redirects the user to the final **Thank You** page without further steps.

5. **Payment Completion (Thank You Page)**  
   The user is redirected to a **Thank You** page to confirm that the payment process has completed.

6. **Payment Confirmation (`PaymentController->confirm()`, administrator)**  
   An administrator confirms the payment with the `payment.confirm` task.  
   - `PaymentHelper::onPaymentCompleted()` emails the client and the administrator and logs the completion.  
   - The status of each invoice linked to the payment is recalculated, so a fully paid invoice is closed.  
   - The `onMothershipPaymentCompleted` event is fired.


---

## Supported Events
- **onAfterInitialiseMothership**: Dispatched by the site dispatcher after the `mothership-payment` plugins are loaded.
- **onMothershipInvoiceOpened($invoice)**: Fired after an invoice is set to `Opened`.
- **onMothershipInvoiceClosed($invoice)**: Fired after an invoice is set to `Closed`.
- **onMothershipPaymentCompleted($payment)**: Fired after a payment is completed and its invoices have been updated.
- **onMothershipProposalPending($proposal)**: Fired after a proposal is set to `Pending`.

## Payment Plugins
Two payment plugins ship with Mothership and are installed with the component: **Pay By Check** and **Zelle**. Payment plugins belong to the `mothership-payment` plugin group. A PayPal plugin is not included in this release.

### Creating a Payment Plugin
To create a Mothership Payment plugin, follow these steps:

1. **Create the Plugin Directory**: Create a new directory for your plugin under the `plugins/mothership-payment` directory. Name the directory according to your payment method, e.g., `mypaymentmethod`.

2. **Create the Plugin Files**: Inside your plugin directory, create the following files:
    - `mypaymentmethod.php`: This is the main plugin file.
    - `mypaymentmethod.xml`: This is the manifest file that describes your plugin.
    - `tmpl/instructions.php`: The payment instructions layout. It is required; the Select Payment Page renders it for every enabled plugin. It receives `invoiceId`, `invoiceNumber`, `id`, `amount`, and `payment_method` in `$displayData`.

3. **Define the Plugin Class**: In `mypaymentmethod.php`, define a class named `PlgMothershipPayment` followed by the plugin name with its first letter capitalized, extending Joomla's `CMSPlugin`. Mothership calls the three methods below.

    ```php
    defined('_JEXEC') or die;

    use Joomla\CMS\Plugin\CMSPlugin;

    class PlgMothershipPaymentMypaymentmethod extends CMSPlugin
    {
         public function initiate($payment, $invoice): bool {
          /* Initiates the payment process for this plugin, usually ending in a redirect */
         }

         public function getFee($amount): float {
          /* The fee this payment method adds to the given amount */
         }

         public function displayFee($amount): string {
          /* The fee as shown to the user on the Select Payment Page */
         }
    }
    ```

4. **Create the Manifest File**: In `mypaymentmethod.xml`, define the plugin metadata and files. The `display_name` parameter is the name shown on the Select Payment Page.

    ```xml
    <extension type="plugin" group="mothership-payment" method="upgrade">
         <name>PLG_MOTHERSHIP_PAYMENT_MYPAYMENTMETHOD</name>
         <author>Your Name</author>
         <version>1.0.0</version>
         <description>My custom payment plugin for Mothership</description>
         <files>
              <filename plugin="mypaymentmethod">mypaymentmethod.php</filename>
              <folder>tmpl</folder>
         </files>
         <config>
              <fields name="params">
                   <fieldset name="basic">
                        <field name="display_name" type="text" label="Display Name" description="Description here" default="My Payment Method" />
                   </fieldset>
              </fields>
         </config>
    </extension>
    ```

5. **Install and Enable the Plugin**: Install the plugin through the Joomla Extension Manager and enable it from the Plugin Manager.

6. **Configure the Plugin**: Add any necessary configuration options in the plugin settings to allow users to enter their payment gateway credentials.

By following these steps, you can create a custom payment plugin for Mothership that integrates with your preferred payment gateway.

### Zelle
This payment method is essentially a digital version of "Pay by Check". Once the payment has been confirmed, an administrator will need to manually confirm the payment.

### Pay By Check
This payment method allows clients to pay invoices by mailing a physical check. Once the check is received and processed, an administrator will need to manually confirm the payment. This method is ideal for clients who prefer traditional payment methods or do not use digital payment platforms.

---

# PDFs
Invoices and proposals can be previewed and downloaded as PDFs, rendered with mPDF. Invoice PDF templates can be supplied by plugins in the `mothership-invoice-pdf` plugin group; the template is selected in the component options and can be set per client.

# Administrator Modules
Four administrator dashboard modules live in `modules/`. They are packaged as separate zips and installed separately from the component.

- **mod_mothership_invoices**: Lists open (unpaid or partially paid) invoices.
- **mod_mothership_invoiced**: Bar chart of monthly invoiced totals for the current year, with the all-time total.
- **mod_mothership_payments**: Bar chart of monthly completed-payment totals for the current year, with the all-time total.
- **mod_mothership_tickets**: Lists the most recently updated support tickets. It belongs to the planned support tickets feature, which is not part of the current release.

# Releases
Release packages are built by the scripts in `build/`:

- **build/set-version.sh <version>**: Sets the version in `mothership.xml`, `composer.json`, and `updates.xml`.
- **build/build.sh**: Builds the installable zips into `dist/` from the committed tree: the component, one zip per payment plugin, and one zip per module.
- **build/update-server.sh <zip>**: Points `updates.xml` (the Joomla update server) at a built component zip, including its size and checksum.

Pushing a version tag runs the `Release` workflow, which first runs the full lint and test workflow against the tagged commit, then checks the tag against `mothership.xml`, builds the packages, publishes the GitHub release, and opens a pull request that points `updates.xml` at the new package.

---

# Helpers

## Mothership Helper
- **getReturnRedirect($default = null)**: Returns the `return` URL from the request, or `$default` when there is none.
- **getMothershipOptions($option_name = null)**: Returns the company options from the component configuration (name, email, address, phone, default rate), or a single option when a name is given.

## Client Helper
- **getClientListOptions()**: Retrieves a list of client options for selection.
- **getClient($client_id)**: Retrieves the details of a specific client based on the provided client ID.

## Account Helper
- **getAccountListOptions($client_id=NULL)**: Retrieves a list of account options for a specific client or all clients if no client ID is provided.
- **getAccount($account_id)**: Retrieves the details of a specific account based on the provided account ID.

## Invoice Helper
- **getStatus($status_id)**: Retrieves the status details for the given status ID.
- **isLate($invoice_id)**: Checks if the specified invoice is past its due date.
- **getDueString(int $invoice_id)**: Retrieves a formatted string indicating the due date of the specified invoice.
- **getDueStringFromDate(?string $dueDate)**: Converts a due date into a human-readable string format.
- **setInvoiceClosed($invoiceId)**: Marks the specified invoice as paid.
- **getInvoiceAppliedPayments($invoiceID)**: Retrieves all payments applied to the specified invoice.
- **sumInvoiceAppliedPayments($invoiceId)**: Calculates the total amount of payments applied to the specified invoice.
- **getInvoice($invoice_id)**: Retrieves the invoice record for the given ID. Throws a `RuntimeException` when it is not found.
- **getInvoicePayments($invoiceId)**: Retrieves the Invoice Payments records for the specified invoice.
- **updateInvoiceStatus($invoice, $status)**: Updates the status of the given invoice object, setting the paid date when the invoice is closed.
- **recalculateInvoiceStatus(int $invoiceId)**: Calculates the status of the specified invoice based on its current payment data and returns the appropriate status; it does not update the invoice record.
- **onInvoiceOpened($invoice, int $previousStatus): void**: Method called when the invoice status is set to opened. Emails the client owner, logs the change, then calls the event `onMothershipInvoiceOpened`.
- **onInvoiceClosed($invoice, int $previousStatus): void**: Method called when the invoice status is set to closed. Emails the client owner, logs the change, then calls the event `onMothershipInvoiceClosed`.


## Payments Helper
The **Payments Helper** provides several methods to manage and update payment records and statuses. Below are the methods available:

- **getPayment($paymentId)**: Retrieves the payment details for the given payment ID.
- **getInvoicePayment($invoiceId, $paymentId)**: Retrieves the payment allocation details for a specific invoice and payment ID.
- **getStatus($status_id)**: Retrieves the status label for the given status ID.
- **insertPaymentRecord(int $clientId, int $accountId, float $amount, $paymentDate, float $fee, $feePassedOn, $paymentMethod, $txnId, int $status)**: Inserts a new payment record with the specified details.
- **insertInvoicePayments($invoiceId, $paymentId, $applied_amount)**: Inserts a payment allocation record for a specific invoice with the applied amount.
- **getPaymentInvoices($paymentId): array**: Retrieves the invoices a payment is applied to.
- **getPendingPayments($invoiceId)**: Retrieves the pending payments for the specified invoice.
- **completePayment(int $paymentId, string $txnId = '', float $feeAmount = 0.0)**: Marks a pending payment as `Completed`, recording the transaction ID and fee. A payment that is already completed is returned unchanged. It does not run the completion side effects; call `onPaymentCompleted()` afterwards.
- **onPaymentCompleted($payment)**: Method called when the payment status is set to `Completed`. It emails the client and the administrator, logs the completion, and recalculates the status of the invoices the payment applies to. This method calls the event `onMothershipPaymentCompleted` which plugins can tie into.

## Domains Helper

- **getDomain(int $domain_id)**: Retrieves the domain information based on the provided domain ID.
- **getStatus(int $status_id)**: Retrieves the domain status based on a provided integer status level
- **scanDomain(string $domainName): array**: Runs a WHOIS lookup for the domain and returns its availability, registrar, dates, name servers, DNS provider, and EPP status.
- **getDomainStatus($epp_status): array**: Cleans up the raw EPP status values returned by WHOIS, removing the trailing description and URL.
- **getDnsProvider(array $name_servers): string**: Derives the DNS provider name from the domain's name servers.

## Project Helper

- **scanWebsiteProject(string $url): array**: Scans the given website URL and returns an array containing information about the website's structure, metadata, and other relevant details.
- **getGeneratorMeta($html)**: Extracts and returns the generator meta tag from the provided HTML content, which typically indicates the CMS or framework used by the website.
- **detectJoomla(array $headers, string $html): bool**: Analyzes the provided HTTP headers and HTML content to determine if the website is powered by Joomla. Returns `true` if Joomla is detected, otherwise `false`.
- **detectWordpress(array $headers, string $html): bool**: Analyzes the provided HTTP headers and HTML content to determine if the website is powered by WordPress. Returns `true` if WordPress is detected, otherwise `false`.
- **getProjectListOptions($account_id = NULL)**: Retrieves a list of project options associated with the specified account. If no account ID is provided, it fetches project options for all accounts. This function is useful for populating dropdowns or selection lists with project data.


## Logs Helper
- **log(array $params)**: Logs a generic event with the provided parameters.
- **logPaymentLifecycle(string $event, int $invoiceId, int $paymentId, ?int $clientId = null, ?int $accountId = null, float $amount = 0.0, string $method = '', ?string $extraDetails = null)**: Logs the lifecycle events of a payment, such as initiation or completion.
- **logPaymentInitiated($invoice_id, $payment_id, $client_id, $account_id, $invoiceTotal, $paymentMethod)**: Logs when a payment process is initiated for a specific invoice.
- **logPaymentCompleted($payment)**: Logs the completion of a payment.
- **logPaymentFailed($paymentId, ?string $reason = null)**: Logs a failed payment attempt with an optional reason.
- **logObjectViewed($object_type, $object_id, $client_id, $account_id)**: Logs when a specific object (e.g., invoice, domain) is viewed.
- **logDomainViewed($client_id, $account_id, $domain_id)**: Logs when a domain is viewed by a user.
- **logProjectViewed($client_id, $account_id, $project_id)**: Logs when a project is viewed by a user.
- **logPaymentViewed($client_id, $account_id, $payment_id)**: Logs when a payment record is viewed by a user.
- **logInvoiceViewed($client_id, $account_id, $invoice_id)**: Logs when an invoice is viewed by a user.
- **logAccountViewed($client_id, $account_id)**: Logs when an account is viewed by a user.
- **logInvoiceStatusOpened($invoice_id, $client_id, $account_id)**: Logs when an invoice status is changed to "Opened."
- **logInvoiceStatusClosed($invoice_id, $client_id, $account_id)**: Logs when an invoice status is changed to "Closed."
- **logStatusChange(object $payment, $newStatus)**: Logs a status change for a payment, including the new status.
- **logDomainScanned($domain_id, $client_id, $account_id = NULL)**: Logs when a domain is scanned.
- **logProjectScanned($project_id, $client_id, $account_id)**: Logs when a project is scanned.

## Proposal Helper
- **getStatus($status_id)**: Retrieves the status label for the given status ID.
- **getProposal($proposal_id)**: Retrieves the proposal record for the given ID. Throws a `RuntimeException` when it is not found.
- **updateProposalStatus($proposal, $status): bool**: Updates the status of the given proposal object.
- **onProposalPending($proposal, int $previousStatus): void**: Method called when the proposal status is set to pending. Then calls the event `onMothershipProposalPending`.

## Provider Helper
- **loadProviders()**: Loads the hosting, DNS, and registrar provider lists from the component's domain services config file.
- **getOptionsByType($type)**: Returns the providers of one type (e.g., `dns`, `registrars`).

## Site Mothership Helper
The front end has its own `MothershipHelper`.

- **getUserClientIds($userId = null): ?array**: Returns the IDs of the clients the user belongs to. Defaults to the current user.
- **getClient($clientId): ?object**: Retrieves the client record for the given client ID.

# Notification Emails
Email templates are layouts in `admin/layouts/emails`, sent through `EmailService::sendTemplate()` and wrapped in the `wrapper` layout.

## User - Invoice Opened
The invoice has been set to `Opened`. This will send the email template `invoice.user-opened` to the Client Owner.

## User - Invoice Closed 
The invoice has been set to `Closed`. This will send the email template `invoice.user-closed` to the Client Owner.

## User - Payment Confirmed
The payment has been confirmed and updated from `Pending` to `Completed`. This will send the email template `payment.user-confirmed` to the client's email address.

## Admin - Payment Confirmed
The payment has been confirmed and updated from `Pending` to `Completed`. This will send the email template `payment.admin-confirmed` to the company email address.

## Admin - Payment Pending
The user has started a new payment. This will send the email template `payment.admin-pending` to the company email address.


# Testing
Mothership is built with stability and confidence in mind. Our automated testing suite uses Codeception to simulate real-world usage across backend and frontend features, ensuring every update works as expected. From invoice lifecycle logic to payment integration and PDF generation, our tests catch regressions early and help maintain a high standard of code quality. Whether you're contributing or deploying, the suite gives you peace of mind that Mothership remains reliable.

**WORK IS NOT CONSIDERED DONE UNTIL IT IS COVERED BY TESTS**

### Continuous Integration
Every pull request and push to `main` runs the `Run Mothership Tests` workflow:

- **Lint**: PHP syntax on the oldest supported PHP version, `shellcheck` on the build scripts, `actionlint` on the workflows, and `php tests/check-groups.php`.
- **Tests**: the functional, api, and integration suites, and the acceptance suite split into one job per `@group` (`backend-client`, `backend-invoice`, `frontend`, and so on).

Every acceptance test must carry one of the groups in the workflow matrix, otherwise it would never run; `tests/check-groups.php` fails the build when one does not. Tests in the `skipCI` group are skipped in CI.

### Running the stack locally
`docker-compose.yml` is the same stack CI uses: Joomla with the component mounted from the checkout, MySQL, Mailpit, and Selenium. `docker compose up -d --wait` serves it on the fixed ports 8080, 3306, 8025 and 1025. Selenium is behind a profile because only the acceptance suite needs it: add `--profile selenium` to start it.

Fixed ports mean only one copy can run at a time. To give each checkout (a git worktree per unit of work, for example) its own Joomla and database, use `docker/instance.sh`:

- **docker/instance.sh up**: Starts an instance named after the checkout's folder, installs Mothership and the admin user the way CI does, and prints the URLs. Ports are random free ports on `127.0.0.1`. Add `--selenium` to start the browser too.
- **docker/instance.sh url**: Prints the URLs again.
- **docker/instance.sh test <suite> [codecept options]**: Runs a suite inside the instance, for example `docker/instance.sh test acceptance --group backend-client`. Starts Selenium first when the suite is `acceptance`.
- **docker/instance.sh compose ...**: Any other `docker compose` command against the instance, such as `logs -f joomla`.
- **docker/instance.sh down**: Stops the instance and deletes its database.
- **docker/instance.sh list**: Lists every instance on the machine.

Put `-n <name>` before the command to run more than one instance from the same checkout. They share `tests/`, so run their test suites one at a time.

### WHOIS in tests
`MothershipDomainHelperTest::testScanDomain` replays a recorded WHOIS response from `tests/_data/whois/google.com.json`, so its exact values do not drift. The live lookup is `testScanDomainLive` in the `network` group, which is not run in CI: `codecept run integration -g network`.

### DB Helpers
These methods for Codeception's DB module allow the database to be quickly configured for testing.

- **createMothershipClientData(array $data)**:
- **createMothershipClient(array $data)**:
- **createJoomlaUserData(array $data)**:
- **createJoomlaUser(array $data)**:
- **createMothershipUserData(array $data)**:
- **createMothershipUser(array $data)**:
- **createMothershipAccountData(array $data)**:
- **createMothershipAccount(array $data)**:
- **createMothershipProposalData(array $data)**: 
- **createMothershipProposal(array $data)**: 
- **createMothershipProposalItemData(array $data)**:
- **createMothershipProposalItem(array $data)**:
- **createMothershipInvoiceData(array $data)**: 
- **createMothershipInvoice(array $data)**: 
- **createMothershipInvoiceItemData(array $data)**:
- **createMothershipInvoiceItem(array $data)**:
- **createMothershipPaymentData(array $data)**:
- **createMothershipPayment(array $data)**:
- **createMothershipProjectData(array $data)**:
- **createMothershipProject(array $data)**:
- **createMothershipInvoicePaymentData(array $data)**:
- **createMothershipInvoicePayment(array $data)**:
- **createMothershipDomainData(array $data)**:
- **createMothershipDomain(array $data)**:
- **createMothershipLogData(array $data)**:
- **createMothershipLog(array $data)**:
- **setInvoiceStatus($invoiceId, $status)**:
- **setProposalStatus($proposalId, $status)**:
- **clearClientsTable()**:
- **clearAccountsTable()**:
- **clearInvoicesTable()**:
- **clearProposalsTable()**:
- **clearInvoiceItemsTable()**:
- **clearProposalItemsTable()**:
- **clearPaymentsTable()**:
- **clearInvoicePaymentTable()**:
- **clearUsersTable()**:
- **resetMothershipTables()**:
- **setMothershipConfig(array $settings)**:
- **grabInvoiceRow($invoiceId, $rowNumber)**:
- **grabDomainFromDatabase($domainId)**:
- **grabProjectFromDatabase($projectId)**:
- **getClientIdByName($clientName)**:
- **grabLastCompletedPaymentId()**:
- **setPaymentStatus($paymentId, $status)**:
- **setPaymentLocked($paymentId)**:
- **setPaymentUnlocked($paymentId)**:
- **setInvoiceLocked($invoiceId)**:
- **setInvoiceUnlocked($invoiceId)**:

### Custom Assertions

- **assertProposalHasRows($proposalId, $expectedRows)**:
- **assertInvoiceHasRows($invoiceId, $expectedRows)**:
- **assertInvoiceStatus(int $invoiceId, string $expectedStatusLabel)**:
- **assertInvoiceStatusDraft($invoiceId)**:
- **assertInvoiceStatusOpened($invoiceId)**:
- **assertInvoiceStatusClosed($invoiceId)**:
- **assertPaymentStatus(int $paymentId, string $expectedStatusLabel)**:
- **assertPaymentStatusDraft($paymentId)**:
- **assertPaymentStatusOpened($paymentId)**:
- **assertPaymentStatusLate($paymentId)**:
- **assertPaymentStatusCompleted($paymentId)**:
- **assertInvoiceClientId($invoiceId, $expectedClientId)**:
- **assertInvoiceHasItems(int $invoiceId, array $expectedItems)**:

### Helpers

- **calculatePaypalFee($amount)**:
- **totalWithPaypalFee($amount)**:

## Acceptance

### Front End
Front end codeception test classes

- **MothershipFrontClientsCest**:
- **MothershipFrontAccountsCest**:
- **MothershipFrontProposalsCest**:
- **MothershipFrontInvoicesCest**:
- **MothershipFrontProjectsCest**:
- **MothershipFrontDomainsCest**:
- **MothershipFrontPayByCheckCest**:
- **MothershipFrontPayByZelleCest**:
- **MothershipFrontPaymentsCest**:

### Back End
Back end codeception test classes.

- **MothershipAdminClientsCest**:
- **MothershipAdminAccountsCest**:
- **MothershipAdminProposalsCest**:
- **MothershipAdminInvoicesCest**:
- **MothershipAdminProjectsCest**:
- **MothershipAdminDomainsCest**:
- **MothershipAdminPaymentsCest**:
- **MothershipAdminLogsCest**:
- **MothershipAdminMenuCest**:
- **MothershipAdminOptionsCest**:
- **MothershipAdminPluginPayByCheckSettingsCest**:
- **MothershipAdminPluginZelleSettingsCest**:

## Functional

- **homepageIsVisible(FunctionalTester $I)**:

## API
There are no API tests yet.

## Integration

- **MothershipClientHelperTest**:
- **MothershipAccountHelperTest**:
- **MothershipProjectHelperTest**:
- **MothershipProposalHelperTest**:
- **MothershipInvoiceHelperTest**:
- **MothershipDomainHelperTest**:
- **MothershipEmailServiceTest**:
- **MothershipPaymentHelperTest**:
- **MothershipPaymentPluginZelleTest**:
- **MothershipHelperTest**:
- **MothershipLogHelperTest**:
