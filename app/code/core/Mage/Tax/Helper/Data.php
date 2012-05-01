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
 * @package    Mage_Tax
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog data helper
 */
class Mage_Tax_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_taxData;
    protected $_priceIncludesTax;

    public function getProductPrice($product, $format=null)
    {
        try {
            $value = $product->getPrice();
            $value = Mage::app()->getStore()->convertPrice($value, $format);
        }
        catch (Exception $e){
            $value = $e->getMessage();
        }
    	return $value;
    }

    public function priceIncludesTax($store=null)
    {
        $storeId = Mage::app()->getStore($store)->getId();
        if (!isset($this->_priceIncludesTax[$storeId])) {
            $this->_priceIncludesTax[$storeId] =
                (int)Mage::getStoreConfig('sales/tax/price_includes_tax', $store)
                && Mage::getStoreConfig('sales/tax/based_on', $store)==='origin';
        }
        return $this->_priceIncludesTax[$storeId];
    }

    /**
     * Output
     *
     * @param boolean $includes
     */
    public function getIncExcText($flag, $store=null)
    {
        if (!$this->priceIncludesTax($store)) {
            return null;
        }
        if ($flag) {
            $s = $this->__('Incl. Tax');
        } else {
            $s = $this->__('Excl. Tax');
        }
        return $s;
    }

    public function getCatalogTaxRate($productClassId, $customerClassId=null, $store=null)
    {
        if (!$this->priceIncludesTax($store)) {
            return false;
        }
        if (is_null($customerClassId)) {
            $customerClassId = Mage::getSingleton('customer/session')->getCustomer()->getTaxClassId();
        }
        $key = $productClassId.'|'.$customerClassId.'|'.Mage::app()->getStore($store)->getId();
        if (!isset($this->_taxData[$key])) {
            $origin = Mage::getStoreConfig('shipping/origin', $store);
            $taxModel = Mage::getModel('tax/rate_data')
                ->setProductClassId($productClassId)
                ->setCustomerClassId($customerClassId)
                ->setCountryId($origin['country_id'])
                ->setRegionId($origin['region_id'])
                ->setPostcode($origin['postcode']);
            $this->_taxData[$key] = $taxModel->getRate();
        }
        return $this->_taxData[$key];
    }
/*
    public function updateProductTax($product)
    {
        $store = Mage::app()->getStore($product->getStoreId());
        $taxRatio = $this->getCatalogTaxRate($product->getTaxClassId(), null, $store);
        if (false===$taxRatio) {
            return false;
        }
        $taxRatio /= 100;
        $product->setPriceAfterTax($store->roundPrice($product->getPrice()*(1+$taxRatio)));
        $product->setFinalPriceAfterTax($store->roundPrice($product->getFinalPrice()*(1+$taxRatio)));
        $product->setShowTaxInCatalog(Mage::getStoreConfig('sales/tax/show_in_catalog', $store));

        return $taxRatio;
    }
*/
}
