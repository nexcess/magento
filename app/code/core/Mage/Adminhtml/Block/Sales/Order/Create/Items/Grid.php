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
 * Adminhtml sales order create items grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Sales_Order_Create_Items_Grid extends Mage_Adminhtml_Block_Sales_Order_Create_Abstract
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_order_create_search_grid');
        $this->setTemplate('sales/order/create/items/grid.phtml');
    }

    protected function _prepareLayout()
    {
        $this->setChild('coupons',
            $this->getLayout()->createBlock('adminhtml/sales_order_create_coupons')
        );
        return parent::_prepareLayout();
    }

    public function getItems()
    {
        return $this->getParentBlock()->getItems();
    }

    public function getSession()
    {
        return $this->getParentBlock()->getSession();
    }

    public function getItemEditablePrice($item)
    {
        return $item->getCalculationPrice()*1;
    }

    public function getItemOrigPrice($item)
    {
        //return $this->convertPrice($item->getProduct()->getPrice());
        return $this->convertPrice($item->getPrice());
    }

    public function isGiftMessagesAvailable($item=null)
    {
        if(is_null($item)) {
            return $this->helper('giftmessage/message')->getIsMessagesAvailable(
                'main', $this->getQuote(), $this->getStore()
            );
        }

        return $this->helper('giftmessage/message')->getIsMessagesAvailable(
            'item', $item, $this->getStore()
        );
    }

    public function isAllowedForGiftMessage($item)
    {
        return Mage::getSingleton('adminhtml/giftmessage_save')->getIsAllowedQuoteItem($item);
    }

    public function getSubtotal()
    {
        $totals = $this->getQuote()->getTotals();
        if (isset($totals['subtotal'])) {
            return $totals['subtotal']->getValue();
        }
        return false;
    }

    public function getSubtotalWithDiscount()
    {
        return $this->getQuote()->getShippingAddress()->getSubtotalWithDiscount();
    }

    public function getDiscountAmount()
    {
        return $this->getQuote()->getShippingAddress()->getDiscountAmount();
    }

    public function usedCustomPriceForItem($item)
    {
        return $item->getCustomPrice();
    }

    public function getQtyTitle($item)
    {
        if ($prices = $item->getProduct()->getTierPrice()) {
            $info = array();
            foreach ($prices as $data) {
                $qty    = $data['price_qty']*1;
                $price  = $this->convertPrice($data['price']);
            	$info[] = $this->helper('sales')->__('Buy %s for price %s', $qty, $price);
            }
            return implode(', ', $info);
        }
        else {
            return $this->helper('sales')->__('Item ordered qty');
        }
    }

    public function getTierHtml($item)
    {
        $html = '';
        if ($prices = $item->getProduct()->getTierPrice()) {
            foreach ($prices as $data) {
                $qty    = $data['price_qty']*1;
                $price  = $this->convertPrice($data['price']);
            	$info[] = $this->helper('sales')->__('%s for %s', $qty, $price);
            }
            $html = implode('<br/>', $info);
        }
        return $html;
    }
}