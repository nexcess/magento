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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


$this->run("
alter table `{$this->getTable('catalog_product_entity_int')}` add index `IDX_ATTRIBUTE_VALUE` (`entity_id`, `attribute_id`, `store_id`);
alter table `{$this->getTable('catalog_product_entity_datetime')}` add index `IDX_ATTRIBUTE_VALUE` (`entity_id`, `attribute_id`, `store_id`);
alter table `{$this->getTable('catalog_product_entity_decimal')}` add index `IDX_ATTRIBUTE_VALUE` (`entity_id`, `attribute_id`, `store_id`);
alter table `{$this->getTable('catalog_product_entity_text')}` add index `IDX_ATTRIBUTE_VALUE` (`entity_id`, `attribute_id`, `store_id`);
alter table `{$this->getTable('catalog_product_entity_varchar')}` add index `IDX_ATTRIBUTE_VALUE` (`entity_id`, `attribute_id`, `store_id`);
");
