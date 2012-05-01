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
 * Adminhtml shipment items grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */

class Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Items extends Mage_Adminhtml_Block_Template
{
    /**
     * Initialize template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/shipment/create/items.phtml');
    }

    /**
     * Prepare child blocks
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'submit_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'label'     => Mage::helper('sales')->__('Submit Shipment'),
                'class'     => 'save submit-button',
                'onclick'   => '$(\'edit_form\').submit()',
            ))
        );

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

    public function formatPrice($price)
    {
        return $this->getShipment()->getOrder()->formatPrice($price);
    }

    public function getUpdateButtonHtml()
    {
        return $this->getChildHtml('update_button');
    }

    public function getUpdateUrl()
    {
        return $this->getUrl('*/*/updateQty', array('order_id'=>$this->getShipment()->getOrderId()));
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
}