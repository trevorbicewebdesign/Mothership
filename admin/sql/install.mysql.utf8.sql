CREATE TABLE IF NOT EXISTS `#__mothership_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address_1` varchar(255) NOT NULL,
  `address_2` varchar(255) NOT NULL,
  `city` varchar(32) NOT NULL,
  `state` varchar(32) NOT NULL,
  `zip` varchar(32) NOT NULL,
  `tax_id` varchar(30) NOT NULL,
  `default_rate` float DEFAULT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `name` (`name`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `#__mothership_accounts` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`client_id` INT(10) NULL DEFAULT NULL,
	`name` VARCHAR(255) NULL DEFAULT NULL,
	`primary_domain` VARCHAR(50) NULL DEFAULT,
	`rate` FLOAT NULL DEFAULT NULL,
	`created` TIMESTAMP NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`checked_out` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=1
;


CREATE TABLE IF NOT EXISTS `#__mothership_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `client_id` INT(11) NOT NULL,
  `role` ENUM('owner', 'employee', 'administrator') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
