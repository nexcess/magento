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
 * @package    Mage_CatalogIndex
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Event observer and indexer running application
 *
 */
class Mage_CatalogIndex_Model_Observer extends Mage_Core_Model_Abstract
{
    protected $_parentProductIds = array();

    protected function _construct() {}

    public function reindexAll()
    {
        Mage::getSingleton('catalogindex/indexer')->reindex();
    }

    public function reindexDaily()
    {
        Mage::getSingleton('catalogindex/indexer')->reindexPrices();
    }

    // event handlers

    public function processAfterSaveEvent(Varien_Event_Observer $observer)
    {
        $eventProduct = $observer->getEvent()->getProduct();
        Mage::getSingleton('catalogindex/indexer')->index($eventProduct);
    }

    public function processPriceScopeChange(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('catalogindex/indexer')->reindexPrices();
    }

    public function processPriceRuleApplication(Varien_Event_Observer $observer)
    {
        $eventProduct = $observer->getEvent()->getProduct();
        Mage::getSingleton('catalogindex/indexer')->reindexPrices($eventProduct);
    }

    public function registerParentIds(Varien_Event_Observer $observer)
    {
        $observer->getEvent()->getProduct()->loadParentProductIds();
    }

    public function processAfterDeleteEvent(Varien_Event_Observer $observer)
    {
        $eventProduct = $observer->getEvent()->getProduct();
        Mage::getSingleton('catalogindex/indexer')->cleanup($eventProduct);
        $parentProductIds = $eventProduct->getParentProductIds();

        foreach ($parentProductIds as $parent) {
            Mage::getSingleton('catalogindex/indexer')->index($parent);
        }
    }

    public function processAttributeChangeEvent(Varien_Event_Observer $observer)
    {
        if ($observer->getAttribute()->getIsFilterable() != 0) {
            Mage::getSingleton('catalogindex/indexer')->reindex();
        }
    }
}