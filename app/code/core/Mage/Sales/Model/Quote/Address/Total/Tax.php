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


class Mage_Sales_Model_Quote_Address_Total_Tax extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $store = $address->getQuote()->getStore();
        $address->setTaxAmount(0);
        $address->setBaseTaxAmount(0);

        $items = $address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        $custTaxClassId = $address->getQuote()->getCustomerTaxClassId();
        $tax = Mage::getModel('tax/rate_data')->setCustomerClassId($custTaxClassId);
        /* @var $tax Mage_Tax_Model_Rate_Data */
        $taxAddress = $address;

        switch (Mage::getStoreConfig('sales/tax/based_on')) {
            case 'billing':
                $taxAddress = $address->getQuote()->getBillingAddress();
                //no break;
            case 'shipping':
                $tax
                    ->setCountryId($taxAddress->getCountryId())
                	->setRegionId($taxAddress->getRegionId())
                	->setPostcode($taxAddress->getPostcode());
                break;

            case 'origin':
                $tax
                    ->setCountryId(Mage::getStoreConfig('shipping/origin/country_id', $store))
                    ->setRegionId(Mage::getStoreConfig('shipping/origin/region_id', $store))
                    ->setPostcode(Mage::getStoreConfig('shipping/origin/postcode', $store));
                break;
        }

        foreach ($items as $item) {
        	$tax->setProductClassId($item->getProduct()->getTaxClassId());
        	$rate = $tax->getRate();
            $item->setTaxPercent($rate);
            $item->calcTaxAmount();

            $address->setTaxAmount($address->getTaxAmount() + $item->getTaxAmount());
            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $item->getBaseTaxAmount());
        }

        $shippingTaxClass = Mage::getStoreConfig('sales/tax/shipping_tax_class', $store);
        if ($shippingTaxClass) {
            $tax->setProductClassId($shippingTaxClass);
            if ($rate = $tax->getRate()) {
                $shippingTax    = $address->getShippingAmount() * $rate/100;
                $shippingBaseTax= $address->getBaseShippingAmount() * $rate/100;
                $shippingTax    = $store->roundPrice($shippingTax);
                $shippingBaseTax= $store->roundPrice($shippingBaseTax);

                $address->setTaxAmount($address->getTaxAmount() + $shippingTax);
                $address->setBaseTaxAmount($address->getBaseTaxAmount() + $shippingBaseTax);
            }
        }
    	if (Mage::helper('tax')->priceIncludesTax($store)) {
    	    $adj = $address->getTotalPriceIncTax()-($address->getSubtotal()+$address->getTaxAmount());
    	    $address->setTaxAmount($address->getTaxAmount()+$adj);

    	    $adj = $address->getBaseTotalPriceIncTax()-($address->getBaseSubtotal()+$address->getBaseTaxAmount());
    	    $address->setBaseTaxAmount($address->getBaseTaxAmount()+$adj);
    	}

        $address->setGrandTotal($address->getGrandTotal() + $address->getTaxAmount());
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseTaxAmount());
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getTaxAmount();
        if ($amount!=0) {
            $address->addTotal(array(
                'code'=>$this->getCode(),
                'title'=>Mage::helper('sales')->__('Tax'),
                'value'=>$amount
            ));
        }
        return $this;
    }
}