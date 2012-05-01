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
 * Creditmemo view form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Sales_Order_Creditmemo_View_Form extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/creditmemo/view/form.phtml');
        $this->setOrder($this->getCreditmemo()->getOrder());
    }

    /**
     * Prepare child blocks
     *
     * @return Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Items
     */
    protected function _prepareLayout()
    {

        $infoBlock = $this->getLayout()->createBlock('adminhtml/sales_order_view_info')
            ->setOrder($this->getCreditmemo()->getOrder());
        $this->setChild('order_info', $infoBlock);

        $totalsBlock = $this->getLayout()->createBlock('adminhtml/sales_order_totals')
            ->setSource($this->getCreditmemo())
            ->setOrder($this->getCreditmemo()->getOrder())
            ->setGrandTotalTitle(Mage::helper('sales')->__('Total Refund'));
        $this->setChild('totals', $totalsBlock);

        $commentsBlock = $this->getLayout()->createBlock('adminhtml/sales_order_comments_view')
            ->setEntity($this->getCreditmemo());
        $this->setChild('comments', $commentsBlock);

        $paymentInfoBlock = $this->getLayout()->createBlock('adminhtml/sales_order_payment')
            ->setPayment($this->getCreditmemo()->getOrder()->getPayment());
        $this->setChild('payment_info', $paymentInfoBlock);
        return parent::_prepareLayout();
    }

    /**
     * Retrieve creditmemo model instance
     *
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function getCreditmemo()
    {
        return Mage::registry('current_creditmemo');
    }

    public function getOrderUrl()
    {
        return $this->getUrl('*/sales_order/view', array('order_id'=>$this->getCreditmemo()->getOrderId()));
    }
}