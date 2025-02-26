CREATE TABLE IF NOT EXISTS `#__mothership_accounts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `primary_domain` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `rate` float DEFAULT NULL,
  `client_id` int(10) DEFAULT NULL,
  `account_created` timestamp NULL DEFAULT NULL,
  `account_activity` timestamp NULL DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=271 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `#__mothership_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `contact_fname` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contact_lname` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contact_phone` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address_2` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `city` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `state` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `zip` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ordering` int(10) NOT NULL,
  `tax_id` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_rate` float DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `status` int(11) DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `#__mothership_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `posted` timestamp NOT NULL DEFAULT current_timestamp(),
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=342 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_format` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_start` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `date_format` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_due_days` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_tax` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `tax` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `currency_symbol` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_company` varchar(70) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `company_email` varchar(70) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `company_phone` varchar(70) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `company_url` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `logo` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `company_address` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `other_details` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_note` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_email_sub` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `rem_email_sub` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_email` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `default_email_rem` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `symbol_display` tinyint(2) NOT NULL,
  `cformat` tinyint(2) NOT NULL,
  `email_cc` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `email_bcc` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `tax_id` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `newusermail_sub` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_layout` tinyint(1) NOT NULL,
  `items_template` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_mode` tinyint(2) NOT NULL,
  `invoice_top_margin` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_bottom_margin` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_left_margin` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_right_margin` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdffontsize_invoice` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `fontname_invoice` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `#__mothership_constants_cache` (
  `name` varchar(50) NOT NULL,
  `value` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE IF NOT EXISTS `#__mothership_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contact_fname` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contact_lname` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address_2` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `city` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `state` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `zip` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ordering` int(10) NOT NULL,
  `tax_id` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_dns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(50) DEFAULT NULL,
  `name` varchar(50) NOT NULL DEFAULT '0',
  `type` varchar(50) DEFAULT NULL,
  `pri` int(11) DEFAULT NULL,
  `value` varchar(50) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `ttl` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(50) DEFAULT NULL,
  `whois` text DEFAULT NULL,
  `redirect` varchar(50) DEFAULT NULL,
  `cloudflare` int(11) DEFAULT 0,
  `domain_registrar` varchar(255) DEFAULT NULL,
  `domain_reseller` varchar(255) DEFAULT NULL,
  `domain_zone` varchar(255) DEFAULT NULL,
  `domain_hosting` varchar(255) DEFAULT NULL,
  `domain_email` varchar(255) DEFAULT NULL,
  `NS1` varchar(255) DEFAULT NULL,
  `NS2` varchar(255) DEFAULT NULL,
  `NS3` varchar(255) DEFAULT NULL,
  `NS4` varchar(255) DEFAULT NULL,
  `modified` datetime DEFAULT NULL COMMENT 'This should be the date this record was last updated',
  `changed` datetime DEFAULT NULL COMMENT 'This should be the domain name ''changed'' date',
  `expiration` datetime DEFAULT NULL COMMENT 'This should be the expiration for the domain registration',
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=254 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '0',
  `tax_type` int(11) NOT NULL DEFAULT 0,
  `item` char(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `paypal_transactionid` varchar(50) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `description` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `merchant` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=900 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `login_url` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `logo` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `domain_regex` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `zone_regex` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `hosting_regex` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `mx_regex` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_invoices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `invoice_date` datetime NOT NULL,
  `client_id` int(11) NOT NULL,
  `account_id` int(10) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `duedate` datetime DEFAULT NULL,
  `numbercheck` int(10) NOT NULL,
  `invoice_sent_date` datetime DEFAULT NULL,
  `communication` int(2) DEFAULT NULL,
  `email_viewed` int(11) NOT NULL DEFAULT 0,
  `pdf_downloaded` int(11) NOT NULL DEFAULT 0,
  `html_viewed` int(11) NOT NULL DEFAULT 0,
  `discount` varchar(25) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '0',
  `subtotal` float NOT NULL DEFAULT 0,
  `totaltax` float NOT NULL DEFAULT 0,
  `total` float NOT NULL DEFAULT 0,
  `item_id` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_name` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_description` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_hours` mediumtext NOT NULL,
  `item_minutes` mediumtext NOT NULL,
  `item_quantity` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_rate` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_price` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_tax` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `note` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `custom_invoice_number` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `reset_inv` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `project_url` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `project_name` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `project_deposit` int(11) DEFAULT 0,
  `waive_paypalfee` int(11) DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1234 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `task` varchar(255) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `params` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `data` text DEFAULT NULL,
  `changed` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=37734 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_log_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller` varchar(255) NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL DEFAULT '0',
  `types` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_mothership` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `httpcode` int(3) DEFAULT NULL,
  `ttfb` float DEFAULT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1186174 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_payments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `payer_email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoices` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `method` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `amount` float DEFAULT NULL,
  `transaction_fee` float unsigned NOT NULL DEFAULT 0,
  `net_total` float NOT NULL,
  `transaction_id` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdate` datetime DEFAULT NULL,
  `status` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `checked_out_time` datetime NOT NULL,
  `checked_out` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=819 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_paypal_mirror` (
  `id` text DEFAULT NULL,
  `transactionid` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_projects` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `archived` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) DEFAULT NULL,
  `favicon` varchar(255) NOT NULL DEFAULT '0',
  `platform` varchar(50) DEFAULT NULL,
  `version` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT NULL,
  `under_construction` int(10) DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT NULL,
  `project_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `primary_domain` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `primary_url` varchar(255) DEFAULT NULL,
  `redirect_test` varchar(255) DEFAULT NULL,
  `www` varchar(255) DEFAULT NULL,
  `non_www` varchar(255) DEFAULT NULL,
  `dev_url` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `build` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `newsite` int(11) DEFAULT NULL,
  `deposit` int(11) DEFAULT NULL,
  `final_payment` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `project_created` date DEFAULT NULL,
  `quote_amount` float DEFAULT NULL,
  `domains` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `httpcode` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `tracking` varchar(50) DEFAULT NULL,
  `name` varchar(50) NOT NULL DEFAULT '0',
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  `scan` int(11) DEFAULT 0,
  `mothership_scan` int(11) DEFAULT 0,
  `extensions` longtext DEFAULT NULL,
  `backups` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=464 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT NULL,
  `template_pieces` varchar(50) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `deposit_invoice_id` int(11) DEFAULT NULL,
  `project_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `project_domain` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `total` float DEFAULT NULL,
  `sitemap` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `approved` int(11) DEFAULT NULL,
  `deposit` int(11) DEFAULT NULL,
  `deposit_date` datetime DEFAULT NULL,
  `summary` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_basic` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_modules` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_template` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_extensions` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_forms` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_other` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `programming_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `items` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `hours` decimal(2,0) DEFAULT NULL,
  `communication` int(11) DEFAULT 0,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(11) DEFAULT 0,
  `client_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `subscription_backup` int(11) DEFAULT NULL,
  `backup_frequency` varchar(255) DEFAULT NULL,
  `backup_database_frequency` varchar(255) DEFAULT NULL,
  `backup_website_type` varchar(50) DEFAULT NULL,
  `backup_summary` text DEFAULT NULL,
  `backup_interval` varchar(50) DEFAULT NULL,
  `backup_website_path` varchar(255) DEFAULT NULL,
  `backup_type` varchar(50) DEFAULT NULL,
  `subscription_updates` varchar(50) DEFAULT NULL,
  `update_frequency` varchar(50) DEFAULT NULL,
  `subscription_malware` int(11) DEFAULT NULL,
  `period` varchar(255) DEFAULT NULL,
  `cost` float DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  `checked_out_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_subscriptions_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `short` varchar(255) DEFAULT NULL,
  `long` text DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `#__mothership_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `invoice_template` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `edit_by` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `edit_date` int(11) NOT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `sample_url` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `short_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `emailCC` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `long_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `note` text DEFAULT NULL,
  `priority` int(11) DEFAULT 0,
  `requestdate` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `status_history` text NOT NULL,
  `submitted` timestamp NULL DEFAULT current_timestamp(),
  `submitted_userid` int(11) DEFAULT NULL,
  `completed` timestamp NULL DEFAULT NULL,
  `hours_estimated` decimal(10,2) DEFAULT NULL,
  `hours_recorded` decimal(10,2) DEFAULT NULL,
  `closedate` timestamp NULL DEFAULT NULL,
  `communication` int(11) NOT NULL DEFAULT 0,
  `ticket_sent_date` datetime DEFAULT NULL,
  `checked_out_time` timestamp NULL DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=875 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_timelog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `description` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `quote_id` int(11) DEFAULT NULL,
  `billable` int(11) DEFAULT NULL,
  `sdate` datetime DEFAULT NULL,
  `edate` datetime DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `checked_out_time` varchar(50) DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4351 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__mothership_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `access` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=FIXED;