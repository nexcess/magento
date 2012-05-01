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
 * Adminhtml invoice items grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */

class Mage_Adminhtml_Block_Sales_Order_Invoice_Create_Items extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Initialize template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/invoice/create/items.phtml');
        $this->setOrder($this->getInvoice()->getOrder());
    }

    /**
     * Prepare child blocks
     *
     * @return Mage_Adminhtml_Block_Sales_Order_Invoice_Create_Items
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('invoice_item_container'),'".$this->getUpdateUrl()."')";
        $this->setChild(
            'update_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'class'     => 'update-button',
                'label'     => Mage::helper('sales')->__('Update Qty\'s'),
                'onclick'   => $onclick,
            ))
        );

        $this->setChild(
            'submit_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'label'     => Mage::helper('sales')->__('Submit Invoice'),
                'class'     => 'save submit-button',
                'onclick'   => '$(\'edit_form\').submit()',
            ))
        );

        $orderPayment = $this->getInvoice()->getOrder()->getPayment();
        $this->setPriceDataObject($orderPayment);
        $totalsBarBlock = $this->getLayout()->createBlock('adminhtml/sales_order_totalbar')
            ->setOrder($this->getInvoice()->getOrder())
            ->addTotal(Mage::helper('sales')->__('Paid Amount'), $this->displayPriceAttribute('amount_paid'))
            ->addTotal(Mage::helper('sales')->__('Refund Amount'), $this->displayPriceAttribute('amount_refunded'))
            ->addTotal(Mage::helper('sales')->__('Shipping Amount'), $this->displayPriceAttribute('shipping_captured'))
            ->addTotal(Mage::helper('sales')->__('Shipping Refund'), $this->displayPriceAttribute('shipping_refunded'));
        $this->setPriceDataObject($this->getInvoice()->getOrder());
        $totalsBarBlock->addTotal(Mage::helper('sales')->__('Order Grand Total'), $this->displayPriceAttribute('grand_total'), true);

        $this->setChild('totals_bar', $totalsBarBlock);

        $totalsBlock = $this->getLayout()->createBlock('adminhtml/sales_order_totals')
            ->setSource($this->getInvoice())
            ->setOrder($this->getInvoice()->getOrder());
        $this->setChild('totals', $totalsBlock);

        return parent::_prepareLayout();
    }

    /**
     * Retrieve invoice model instance
     *
     * @return Mage_Sales_Model_Invoice
     */
    public function getInvoice()
    {
        return Mage::registry('current_invoice');
    }

    public function canEditQty()
    {
        if ($this->getInvoice()->getOrder()->getPayment()->canCapture()) {
            return $this->getInvoice()->getOrder()->getPayment()->canCapturePartial();
        }
        return true;
    }

    public function formatPrice($price)
    {
        return $this->getInvoice()->getOrder()->formatPrice($price);
    }

    public function getUpdateButtonHtml()
    {
        return $this->getChildHtml('update_button');
    }

    public function getUpdateUrl()
    {
        return $this->getUrl('*/*/updateQty', array('order_id'=>$this->getInvoice()->getOrderId()));
    }

    protected function _getQtyBlock()
    {
        $block = $this->getData('_qty_block');
        if (is_null($block)) {
            $block = $this->getLayout()->createBlock('adminhtml/sales_order_item_qty');
            $this->setData('_qty_block', $block);
        }
        return $block;
    }

    public function getQtyHtml($item)
    {
        $html = $this->_getQtyBlock()
            ->setItem($item)
            ->toHtml();
        return $html;
    }

    /**
     * Check shipment availability for current invoice
     *
     * @return bool
     */
    public function canCreateShipment()
    {
        foreach ($this->getInvoice()->getAllItems() as $item) {
            if ($item->getOrderItem()->getQtyToShip()) {
                return true;
            }
        }
        return false;
    }

    public function canCapture()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/capture')) {
            return $this->getInvoice()->canCapture();
        }
        return false;
    }
}