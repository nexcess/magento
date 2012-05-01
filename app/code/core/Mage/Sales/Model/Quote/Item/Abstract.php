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
 * Quote item abstract model
 *
 * @category   Mage
 * @package    Mage_Sales
 */
abstract class Mage_Sales_Model_Quote_Item_Abstract extends Mage_Core_Model_Abstract
{
    abstract function getQuote();

    /**
     * Retrieve store model object
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->getQuote()->getStore();
    }

    /**
     * Import item data from product model object
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function importCatalogProduct(Mage_Catalog_Model_Product $product)
    {
        $this->setProductId($product->getId())
            ->setSku($product->getSku())
            ->setName($product->getName())
            ->setWeight($product->getWeight())
            ->setTaxClassId($product->getTaxClassId())
            ->setCost($product->getCost())
            ->setIsQtyDecimal($product->getIsQtyDecimal());

        if($product->getSuperProduct()) {
            $this->setSuperProductId($product->getSuperProduct()->getId());
            if ($product->getSuperProduct()->isConfigurable()) {
                $this->setName($product->getSuperProduct()->getName());
            }
        }
        $this->setProduct($product);

        return $this;
    }

    /**
     * Checking item data
     *
     * @return Mage_Sales_Model_Quote_Item_Abstract
     */
    public function checkData()
    {
        $qty = $this->getData('qty');
        try {
            $this->setQty($qty);
        }
        catch (Mage_Core_Exception $e){
            $this->setHasError(true);
            $this->setMessage($e->getMessage());
        }
        catch (Exception $e){
            $this->setHasError(true);
            $item->setMessage(Mage::helper('sales')->__('Item qty declare error'));
        }
        return $this;
    }

    /**
     * Calculate item row total price
     *
     * @return Mage_Sales_Model_Quote_Item
     */
    public function calcRowTotal()
    {
        $total      = $this->getCalculationPrice()*$this->getQty();
        $baseTotal  = $this->getBaseCalculationPrice()*$this->getQty();

        $this->setRowTotal($this->getStore()->roundPrice($total));
        $this->setBaseRowTotal($this->getStore()->roundPrice($baseTotal));
        return $this;
    }

    /**
     * Calculate item row total weight
     *
     * @return Mage_Sales_Model_Quote_Item
     */
    public function calcRowWeight()
    {
        $this->setRowWeight($this->getWeight()*$this->getQty());
        return $this;
    }

    /**
     * Calculate item tax amount
     *
     * @return Mage_Sales_Model_Quote_Item
     */
    public function calcTaxAmount()
    {
        $store = $this->getStore();

        //$priceIncludesTax = Mage::getStoreConfig('sales/tax/price_includes_tax', $store);
        $taxAfterDiscount = Mage::getStoreConfig('sales/tax/apply_after_discount', $store);

        if (/*!$priceIncludesTax && */$taxAfterDiscount) {
            $rowTotal       = $this->getRowTotalWithDiscount();
            $rowBaseTotal   = $this->getBaseRowTotalWithDiscount();
        }
        else {
            $rowTotal       = $this->getRowTotal();
            $rowBaseTotal   = $this->getBaseRowTotal();
        }

        $taxPercent = $this->getTaxPercent()/100;
        $this->setTaxAmount($store->roundPrice($rowTotal * $taxPercent));
        $this->setBaseTaxAmount($store->roundPrice($rowBaseTotal * $taxPercent));
/*
        if ($priceIncludesTax) {
            $this->setRowTotal($rowTotal-$this->getTaxAmount());
            $this->setBaseRowTotal($rowBaseTotal-$this->getBaseTaxAmount());

            $this->setPrice($store->roundPrice($this->getPrice()*(1-$taxPercent)));
            $this->setBasePrice($store->roundPrice($this->getBasePrice()*(1-$taxPercent)));
        }
*/
        return $this;
    }

    /**
     * Retrieve item price used for calculation
     *
     * @return unknown
     */
    public function getCalculationPrice()
    {
        $price = $this->getData('calculation_price');
        if (is_null($price)) {
            if ($this->getCustomPrice()) {
                $price = $this->getCustomPrice();
            }
            else {
                $price = $this->getOriginalPrice();
            }
            $this->setData('calculation_price', $price);
        }
        return $price;
    }

    /**
     * Retrieve calculation price in base currency
     *
     * @return unknown
     */
    public function getBaseCalculationPrice()
    {
        if (!$this->hasBaseCalculationPrice()) {
            if ($price = $this->getCustomPrice()) {
                $rate = $this->getStore()->convertPrice($price) / $price;
                $price = $price / $rate;
            }
            else {
                $price = $this->getPrice();
            }
            $this->setBaseCalculationPrice($price);
        }
        return $this->getData('base_calculation_price');
    }

    /**
     * Retrieve original price (retrieved from product) for item
     *
     * @return float
     */
    public function getOriginalPrice()
    {
        $price = $this->getData('original_price');
        if (is_null($price)) {
            $price = $this->getStore()->convertPrice($this->getPrice());
            $this->setData('original_price', $price);
        }
        return $price;
    }
}