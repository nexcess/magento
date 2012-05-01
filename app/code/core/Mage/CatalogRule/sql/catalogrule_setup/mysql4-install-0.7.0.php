<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Mage_CatalogRule
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('catalogrule')};
CREATE TABLE {$this->getTable('catalogrule')} (
  `rule_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `from_date` date default NULL,
  `to_date` date default NULL,
  `store_ids` varchar(255) NOT NULL default '',
  `customer_group_ids` varchar(255) NOT NULL default '',
  `is_active` tinyint(1) NOT NULL default '0',
  `conditions_serialized` text NOT NULL,
  `actions_serialized` text NOT NULL,
  `stop_rules_processing` tinyint(1) NOT NULL default '1',
  `sort_order` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`rule_id`),
  KEY `sort_order` (`is_active`,`sort_order`,`to_date`,`from_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert  into {$this->getTable('catalogrule')}(`rule_id`,`name`,`description`,`from_date`,`to_date`,`store_ids`,`customer_group_ids`,`is_active`,`conditions_serialized`,`actions_serialized`,`stop_rules_processing`,`sort_order`) values (1,'Sony Sale','20% discount on all Sony products.','2007-08-25','2007-08-26','4','1',1,'a:5:{s:4:\"type\";s:34:\"catalogrule/rule_condition_combine\";s:9:\"attribute\";s:3:\"all\";s:8:\"operator\";s:1:\"1\";s:5:\"value\";b:1;s:10:\"conditions\";a:2:{i:0;a:4:{s:4:\"type\";s:34:\"catalogrule/rule_condition_product\";s:9:\"attribute\";s:12:\"manufacturer\";s:8:\"operator\";s:2:\"==\";s:5:\"value\";s:4:\"Sony\";}i:1;a:4:{s:4:\"type\";s:34:\"catalogrule/rule_condition_product\";s:9:\"attribute\";s:5:\"price\";s:8:\"operator\";s:2:\"<=\";s:5:\"value\";s:3:\"100\";}}}','a:5:{s:4:\"type\";s:34:\"catalogrule/rule_action_collection\";s:9:\"attribute\";N;s:8:\"operator\";s:1:\"=\";s:5:\"value\";N;s:7:\"actions\";a:1:{i:0;a:4:{s:4:\"type\";s:31:\"catalogrule/rule_action_product\";s:9:\"attribute\";s:10:\"rule_price\";s:8:\"operator\";s:10:\"by_percent\";s:5:\"value\";s:2:\"20\";}}}',1,0),(3,'CODEDEMOSTORE','10% off all Toshiba laptops','2007-08-06','2009-08-23','1','0,1,2,4',1,'a:5:{s:4:\"type\";s:34:\"catalogrule/rule_condition_combine\";s:9:\"attribute\";s:3:\"all\";s:8:\"operator\";s:1:\"1\";s:5:\"value\";b:1;s:10:\"conditions\";a:1:{i:0;a:4:{s:4:\"type\";s:34:\"catalogrule/rule_condition_product\";s:9:\"attribute\";s:12:\"manufacturer\";s:8:\"operator\";s:2:\"==\";s:5:\"value\";s:6:\"M285-E\";}}}','a:5:{s:4:\"type\";s:34:\"catalogrule/rule_action_collection\";s:9:\"attribute\";N;s:8:\"operator\";s:1:\"=\";s:5:\"value\";N;s:7:\"actions\";a:1:{i:0;a:4:{s:4:\"type\";s:31:\"catalogrule/rule_action_product\";s:9:\"attribute\";s:10:\"rule_price\";s:8:\"operator\";s:10:\"by_percent\";s:5:\"value\";s:2:\"10\";}}}',0,0);


-- DROP TABLE IF EXISTS {$this->getTable('catalogrule_product')};
CREATE TABLE {$this->getTable('catalogrule_product')} (
  `rule_product_id` int(10) unsigned NOT NULL auto_increment,
  `rule_id` int(10) unsigned NOT NULL default '0',
  `from_time` int(10) unsigned NOT NULL default '0',
  `to_time` int(10) unsigned NOT NULL default '0',
  `store_id` smallint(5) unsigned NOT NULL default '0',
  `customer_group_id` smallint(5) unsigned NOT NULL default '0',
  `product_id` int(10) unsigned NOT NULL default '0',
  `action_operator` enum('to_fixed','to_percent','by_fixed','by_percent') NOT NULL default 'to_fixed',
  `action_amount` decimal(12,4) NOT NULL default '0.0000',
  `action_stop` tinyint(1) NOT NULL default '0',
  `sort_order` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`rule_product_id`),
  UNIQUE KEY `sort_order` (`from_time`,`to_time`,`store_id`,`customer_group_id`,`product_id`,`sort_order`),
  KEY `FK_catalogrule_product_rule` (`rule_id`),
  KEY `FK_catalogrule_product_store` (`store_id`),
  KEY `FK_catalogrule_product_customergroup` (`customer_group_id`),
  CONSTRAINT `FK_catalogrule_product_customergroup` FOREIGN KEY (`customer_group_id`) REFERENCES {$this->getTable('customer_group')} (`customer_group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_catalogrule_product_rule` FOREIGN KEY (`rule_id`) REFERENCES {$this->getTable('catalogrule')} (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_catalogrule_product_store` FOREIGN KEY (`store_id`) REFERENCES {$this->getTable('core_store')} (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('catalogrule_product_price')};
CREATE TABLE {$this->getTable('catalogrule_product_price')} (
  `rule_product_price_id` int(10) unsigned NOT NULL auto_increment,
  `rule_date` date NOT NULL default '0000-00-00',
  `store_id` smallint(5) unsigned NOT NULL default '0',
  `customer_group_id` smallint(5) unsigned NOT NULL default '0',
  `product_id` int(10) unsigned NOT NULL default '0',
  `rule_price` decimal(12,4) NOT NULL default '0.0000',
  PRIMARY KEY  (`rule_product_price_id`),
  UNIQUE KEY `rule_date` (`rule_date`,`store_id`,`customer_group_id`,`product_id`),
  KEY `FK_catalogrule_product_price_store` (`store_id`),
  KEY `FK_catalogrule_product_price_customergroup` (`customer_group_id`),
  CONSTRAINT `FK_catalogrule_product_price_customergroup` FOREIGN KEY (`customer_group_id`) REFERENCES {$this->getTable('customer_group')} (`customer_group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_catalogrule_product_price_store` FOREIGN KEY (`store_id`) REFERENCES {$this->getTable('core_store')} (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
