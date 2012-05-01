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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shipment view form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Sales_Order_Shipment_View_Form extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/shipment/view/form.phtml');
        $this->setOrder($this->getShipment()->getOrder());
    }

    /**
     * Prepare child blocks
     *
     * @return Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Items
     */
    protected function _prepareLayout()
    {
        $infoBlock = $this->getLayout()->createBlock('adminhtml/sales_order_view_info')
            ->setOrder($this->getShipment()->getOrder());
        $this->setChild('order_info', $infoBlock);

        $this->setChild('tracking',
            $this->getLayout()->createBlock('adminhtml/sales_order_shipment_view_tracking')
        );

        $commentsBlock = $this->getLayout()->createBlock('adminhtml/sales_order_comments_view')
            ->setEntity($this->getShipment());
        $this->setChild('comments', $commentsBlock);

        $paymentInfoBlock = $this->getLayout()->createBlock('adminhtml/sales_order_payment')
            ->setPayment($this->getShipment()->getOrder()->getPayment());
        $this->setChild('payment_info', $paymentInfoBlock);
        return parent::_prepareLayout();
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }
}