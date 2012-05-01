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
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Checkout_Block_Cart_Shipping extends Mage_Checkout_Block_Cart_Abstract
{
    public function getEstimateRates()
    {
        if (empty($this->_rates)) {
            $groups = $this->getAddress()->getGroupedAllShippingRates();
            if (!empty($groups)) {
                $ratesFilter = new Varien_Filter_Object_Grid();
                $ratesFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'price');

                foreach ($groups as $code => $groupItems) {
                	$groups[$code] = $ratesFilter->filter($groupItems);
                }
            }
            return $this->_rates = $groups;
        }
        return $this->_rates;
    }

    public function getAddress()
    {
        if (empty($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }
        return $this->_address;
    }

    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title')) {
            return $name;
        }
        return $carrierCode;
    }

    public function getAddressShippingMethod()
    {
        return $this->getAddress()->getShippingMethod();
    }

    public function getEstimateCountryId()
    {
        return $this->getAddress()->getCountryId();
    }

    public function getEstimatePostcode()
    {
        return $this->getAddress()->getPostcode();
    }

    public function getEstimateCity()
    {
        return $this->getAddress()->getCity();
    }

    public function getEstimateRegionId()
    {
        return $this->getAddress()->getRegionId();
    }

    public function getEstimateRegion()
    {
        return $this->getAddress()->getRegion();
    }

    public function getCityActive()
    {
        return (bool)Mage::getStoreConfig('carriers/dhl/active');
    }

    public function getStateActive()
    {
        return (bool)Mage::getStoreConfig('carriers/dhl/active') || (bool)Mage::getStoreConfig('carriers/tablerate/active');
    }
}