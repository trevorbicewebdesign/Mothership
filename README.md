# Mothership
Mothership is a friendly, open-source Joomla extension built for solo developers and small businesses. It started as a personal project designed to simplify everyday challenges like invoicing, support tickets, time tracking, expenses, taxes, and logs. Over time, it evolved into a tool that handles all these essential functions in one place—making life easier not just for me, but for anyone juggling the many hats of a web development operation.

At its core, Mothership organizes your business around five main objects:

- **Clients**
- **Accounts**
- **Projects**
- **Invoices**
- **Payments**

The model is flexible enough to accommodate real-world complexities. For example, a single client might manage multiple accounts—whether these represent different parts of the business, subdomains, or entirely separate brands. Each account can then have its own projects. While projects currently focus on websites, the architecture is designed to eventually support other types of work, like graphic design or any other service you might offer.

One of the standout features in the initial release is the projects module. Often, a big part of launching a new website involves researching the domain—finding out details like hosting information, email configurations, and even identifying the underlying CMS or technologies in use. Mothership’s scanning feature does exactly that, gathering vital domain data and making it easy to have informed discussions with your clients. And because these tools are available from the front end, your clients can also use them to better understand their projects.

In short, Mothership is built to streamline your workflow and let you focus on what really matters—delivering great work and growing your business.

## Clients

```
CREATE TABLE `jos_mothership_clients` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`email` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`phone` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`address_1` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`address_2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`city` VARCHAR(32) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`state` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`zip` VARCHAR(32) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`tax_id` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`default_rate` FLOAT NULL DEFAULT NULL,
	`owner_user_id` INT(11) NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP),
	`created_by` INT(11) NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`checked_out` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `name` (`name`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=82
;
```

## Accounts

```
CREATE TABLE `jos_mothership_accounts` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`client_id` INT(10) NULL DEFAULT NULL,
	`name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
	`primary_domain` VARCHAR(50) NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
	`rate` FLOAT NULL DEFAULT NULL,
	`created` TIMESTAMP NULL DEFAULT (now()),
	`created_by` INT(11) NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`checked_out` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=273
;
```

## Projects

## Invoices

```
CREATE TABLE `jos_mothership_invoices` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`number` VARCHAR(50) NOT NULL DEFAULT '0' COLLATE 'utf8_general_ci',
	`client_id` INT(10) NULL DEFAULT NULL,
	`account_id` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
	`rate` FLOAT NULL DEFAULT NULL,
	`status` INT(11) NULL DEFAULT '1',
	`total` FLOAT NULL DEFAULT '0',
	`due_date` DATE NULL DEFAULT NULL,
	`sent_date` DATE NULL DEFAULT NULL,
	`paid_date` DATE NULL DEFAULT NULL,
	`created` TIMESTAMP NULL DEFAULT NULL,
	`created_by` INT(11) NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`checked_out` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=2
;
```

```
CREATE TABLE `jos_mothership_invoice_items` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`invoice_id` INT(10) NOT NULL,
	`name` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
	`description` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci',
	`hours` INT(11) NULL DEFAULT NULL,
	`minutes` INT(11) NULL DEFAULT NULL,
	`quantity` FLOAT NOT NULL DEFAULT '1',
	`rate` FLOAT NOT NULL DEFAULT '0',
	`subtotal` FLOAT NOT NULL DEFAULT '0',
	`ordering` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `invoice_id` (`invoice_id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=39
;
```

## Payments

```
CREATE TABLE `jos_mothership_payments` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`userid` INT(11) NULL DEFAULT NULL,
	`client_id` INT(11) NULL DEFAULT NULL,
	`account_id` INT(11) NULL DEFAULT NULL,
	`name` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci',
	`payer_email` VARCHAR(255) NOT NULL COLLATE 'latin1_swedish_ci',
	`invoices` VARCHAR(255) NOT NULL COLLATE 'latin1_swedish_ci',
	`method` VARCHAR(100) NOT NULL COLLATE 'latin1_swedish_ci',
	`amount` FLOAT NULL DEFAULT NULL,
	`transaction_fee` FLOAT UNSIGNED NOT NULL DEFAULT '0',
	`net_total` FLOAT NOT NULL,
	`transaction_id` VARCHAR(100) NOT NULL COLLATE 'latin1_swedish_ci',
	`pdate` DATETIME NULL DEFAULT NULL,
	`status` CHAR(2) NOT NULL COLLATE 'latin1_swedish_ci',
	`checked_out_time` DATETIME NOT NULL,
	`checked_out` INT(11) NOT NULL,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=819
;
```
