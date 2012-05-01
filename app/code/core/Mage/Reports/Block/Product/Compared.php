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
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Reports Recently Compared Products Block
 *
 * @category   Mage
 * @package    Mage_Reports
 */

class Mage_Reports_Block_Product_Compared extends Mage_Catalog_Block_Product_Abstract
{
    public function __construct()
    {
        parent::__construct();
//        $this->setTemplate('reports/product_compared.phtml');

        $ignore = array();
        foreach (Mage::helper('catalog/product_compare')->getItemCollection() as $_item) {
            $ignore[] = $_item->getId();
        }

        if (($product = Mage::registry('product')) && $product->getId()) {
            $ignore[] = $product->getId();
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer->getId()) {
            $subjectId = $customer->getId();
            $subtype = 0;
        } else {
            $subjectId = Mage::getSingleton('log/visitor')->getId();
            $subtype = 1;
        }
        $collection = Mage::getModel('reports/event')
            ->getCollection()
            ->addRecentlyFiler(3, $subjectId, $subtype, $ignore);
        $productIds = array();
        foreach ($collection as $event) {
            $productIds[] = $event->getObjectId();
        }
        unset($collection);
        $productCollection = null;
        if ($productIds) {
            $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('small_image')
                ->addUrlRewrite()
                ->addIdFilter($productIds);
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
            $productCollection->setPageSize(5)->setCurPage(1)->load();
        }
        $this->setRecentlyComparedProducts($productCollection);
    }
}