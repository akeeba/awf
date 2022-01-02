/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

/*
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

/*
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

/*
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

CREATE TABLE `#__dbtest` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(50) NOT NULL,  `start_date` datetime NOT NULL,  `description` varchar(255) NOT NULL,  PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_innodb` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(50) NOT NULL,  `start_date` datetime NOT NULL,  `description` varchar(255) NOT NULL,  PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `#__users` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `name` varchar(50) NOT NULL, `email` varchar(50) NOT NULL, `username` varchar(50) NOT NULL, `password` varchar(255) NOT NULL, `parameters` varchar(255) NOT NULL,  PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__fakeapp_tests` (  `fakeapp_test_id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(50) NOT NULL,  `start_date` datetime NOT NULL,  `description` varchar(255) NOT NULL,  PRIMARY KEY (`fakeapp_test_id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_defaults` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(50) NOT NULL DEFAULT 'dummy',  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',  `description` varchar(255) NOT NULL,  PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_extended` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(50) NOT NULL,  `start_date` datetime NOT NULL,  `description` varchar(255) NOT NULL, `ordering` TINYINT NOT NULL DEFAULT '0', `enabled` TINYINT NOT NULL DEFAULT '0', `locked_on` DATETIME NULL DEFAULT '0000-00-00 00:00:00',  `locked_by` INT NULL DEFAULT '0', `created_by` INT NOT NULL DEFAULT '0', `created_on` DATETIME DEFAULT '0000-00-00 00:00:00', `modified_on` DATETIME NULL DEFAULT '0000-00-00 00:00:00',  `modified_by` INT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_lockedby` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `locked_by` INT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_lockedon` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `locked_on` INT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_modifiedby` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `modified_by` INT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_modifiedon` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `modified_on` INT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_alias` (  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(50) NOT NULL,  `start_date` datetime NOT NULL,  `description` varchar(255) NOT NULL, `xx_ordering` TINYINT NOT NULL DEFAULT '0', `xx_enabled` TINYINT NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_nestedsets` (`dbtest_nestedset_id` int(11) unsigned NOT NULL AUTO_INCREMENT, `title` varchar(255) NOT NULL DEFAULT '', `slug` varchar(255) NOT NULL DEFAULT '', `lft` int(11) DEFAULT NULL, `rgt` int(11) DEFAULT NULL, `hash` char(40) DEFAULT NULL,  PRIMARY KEY (`dbtest_nestedset_id`),  KEY `lft` (`lft`),  KEY `rgt` (`rgt`),  KEY `lft_2` (`lft`,`rgt`),  KEY `char` (`hash`))  ENGINE=MEMORY DEFAULT CHARSET=utf8;
CREATE TABLE `#__dbtest_nestedbares` ( `id` int(11) unsigned NOT NULL AUTO_INCREMENT,  `title` varchar(255) NOT NULL DEFAULT '',  `lft` int(11) DEFAULT NULL,  `rgt` int(11) DEFAULT NULL,  PRIMARY KEY (`id`),  KEY `lft` (`lft`),  KEY `rgt` (`rgt`),  KEY `lft_2` (`lft`,`rgt`))  ENGINE=MEMORY DEFAULT CHARSET=utf8;

CREATE TABLE `#__fakeapp_parents` (`fakeapp_parent_id` INT NOT NULL AUTO_INCREMENT,`description` varchar (50) NOT NULL , PRIMARY KEY (`fakeapp_parent_id`)) ENGINE=MEMORY;
CREATE TABLE `#__fakeapp_children` (`fakeapp_child_id` INT NOT NULL AUTO_INCREMENT, `description` varchar (50) NOT NULL , `fakeapp_parent_id` INT NOT NULL, `modified_by` INT NOT NULL DEFAULT '0', `modified_on` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY (`fakeapp_child_id`)) ENGINE=MEMORY;
CREATE TABLE `#__fakeapp_parts`(`fakeapp_part_id` INT NOT NULL AUTO_INCREMENT , `description` varchar (50) NOT NULL , PRIMARY KEY (`fakeapp_part_id`)) ENGINE=MEMORY;
CREATE TABLE `#__fakeapp_groups`( `fakeapp_group_id` INT NOT NULL AUTO_INCREMENT , `description` varchar (50) NOT NULL , PRIMARY KEY (`fakeapp_group_id`)) ENGINE=MEMORY;
CREATE TABLE `#__fakeapp_parts_groups`(`fakeapp_group_id` INT NOT NULL , `fakeapp_part_id` INT NOT NULL) ENGINE=MEMORY;