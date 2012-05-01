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
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales order view block
 *
 * @category   Mage
 * @package    Mage_Sales
 */
class Mage_Sales_Block_Reorder_Sidebar extends Mage_Core_Block_Template
{

    public function __construct()
    {
        parent::__construct();

        if (Mage::getSingleton('customer/session')->getCustomer()->getId()) {
    	    $this->setTemplate('sales/order/history.phtml');

            $orders = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
                ->addAttributeToSort('created_at', 'desc')
                ->setPage(1,1);
            //TODO: add filter by current website

            $this->setOrders($orders);

        }
    }

    public function _prepareLayout()
    {
        parent::_prepareLayout();
//        $this->getLayout()->getBlock('root')
//            ->setHeaderTitle(Mage::helper('sales')->__('My Orders'));
    }

    public function getLastOrder()
    {
        foreach ($this->getOrders() as $order) {
//            $order =  Mage::getModel('sales/order')->load($order->getId());
//
//            $collection = Mage::getModel('sales/order_item')->getCollection()
//                ->setOrderFilter($order->getId())
//                ->setPageSize(2)
//                ->load();
//            var_dump($collection->getItems());
//            foreach ($order->getItemsCollection() as $item) {
//                $products[] = $item->getProductId();
//            }
//            $productsCollection = Mage::getModel('catalog/product')
//                ->getCollection()
//                ->addIdFilter($products)
//                ->load();
//            foreach ($order->getItemsCollection() as $item) {
//                $item->setProduct($productsCollection->getItemById($item->getProductId()));
//            }
            return $order;
        }
        return false;
    }
//    public function loadItem($item){
//        return Mage::getModel('catalog/product')->load($item->getId());
//    }
    protected function _toHtml()
    {
        if (Mage::helper('sales/reorder')->isAllow() && Mage::getSingleton('customer/session')->getCustomer()->getId()) {
            return parent::_toHtml();
        }
        return '';
    }
}