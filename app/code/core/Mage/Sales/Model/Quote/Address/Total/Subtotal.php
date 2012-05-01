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


class Mage_Sales_Model_Quote_Address_Total_Subtotal extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     * Collect address subtotal
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Quote_Address_Total_Subtotal
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $address->setSubtotal(0);
        $address->setBaseSubtotal(0);

        $address->setTotalQty(0);

        $address->setBaseTotalPriceIncTax(0);

        $items = $address->getAllItems();

        foreach ($items as $item) {
        	if (!$this->_initItem($address, $item) || $item->getQty()<=0) {
        	    $this->_removeItem($address, $item);
        	}
        }

        $address->setGrandTotal($address->getSubtotal());
        $address->setBaseGrandTotal($address->getBaseSubtotal());
        Mage::helper('sales')->checkQuoteAmount($address->getQuote(), $address->getSubtotal());
        Mage::helper('sales')->checkQuoteAmount($address->getQuote(), $address->getBaseSubtotal());
        return $this;
    }

    /**
     * Address item initialization
     *
     * @param  $item
     * @return bool
     */
    protected function _initItem($address, $item)
    {
    	if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
    	    $quoteItem = $item->getAddress()->getQuote()->getItemById($item->getQuoteItemId());
    	}
    	else {
    	    $quoteItem = $item;
    	}
    	$product = $quoteItem->getProduct();
    	$product->setCustomerGroupId($quoteItem->getQuote()->getCustomerGroupId());
    	$superProduct = $quoteItem->getSuperProduct();

    	/**
    	 * Quote super mode flag meen whot we work with quote
    	 * without restriction
    	 */
    	if ($item->getQuote()->getIsSuperMode()) {
            if (!$product) {
                return false;
            }
    	}
    	else {
        	if (!$product || !$product->isVisibleInCatalog() || ($superProduct && !$superProduct->isVisibleInCatalog())) {
                return false;
            }
    	}

    	$finalPrice = $product->getFinalPrice($quoteItem->getQty());
    	$store = $quoteItem->getStore();
    	$priceIncludesTax = Mage::helper('tax')->priceIncludesTax($store);
    	if ($priceIncludesTax) {
            $item->setBasePriceIncludingTax($finalPrice);
            $taxRate = Mage::helper('tax')->getCatalogTaxRate(
                $quoteItem->getTaxClassId(),
                $address->getQuote()->getCustomerTaxClassId(),
                $store
            )/100;
            $item->setPrice($store->roundPrice($finalPrice/(1+$taxRate)));
    	} else {
        	$item->setPrice($finalPrice);
    	}

    	$item->calcRowTotal();

        $address->setSubtotal($address->getSubtotal() + $item->getRowTotal());
        $address->setBaseSubtotal($address->getBaseSubtotal() + $item->getBaseRowTotal());
        $address->setTotalQty($address->getTotalQty() + $item->getQty());

        if ($priceIncludesTax) {
            $totalPrice = $address->getTotalPriceIncTax()+$store->convertPrice($finalPrice)*$item->getQty();
            $address->setTotalPriceIncTax($totalPrice);

            $totalPrice = $address->getBaseTotalPriceIncTax()+$finalPrice*$item->getQty();
            $address->setBaseTotalPriceIncTax($totalPrice);
        }
        return true;
    }

    /**
     * Remove item
     *
     * @param  $address
     * @param  $item
     * @return Mage_Sales_Model_Quote_Address_Total_Subtotal
     */
    protected function _removeItem($address, $item)
    {
	    if ($item instanceof Mage_Sales_Model_Quote_Item) {
	        $address->removeItem($item->getId());
            if ($address->getQuote()) {
                $address->getQuote()->removeItem($item->getId());
            }
	    }
	    elseif ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
	        $address->removeItem($item->getId());
            if ($address->getQuote()) {
                $address->getQuote()->removeItem($item->getQuoteItemId());
            }
	    }

	    return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $address->addTotal(array(
            'code'=>$this->getCode(),
            'title'=>Mage::helper('sales')->__('Subtotal'),
            'value'=>$address->getSubtotal()
        ));

        return $this;
    }
}