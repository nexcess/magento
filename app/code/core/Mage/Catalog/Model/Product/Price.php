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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Catalog_Model_Product_Price extends Varien_Object
{
    protected function _getCustomerGroupId($product)
    {
        if ($product->getCustomerGroupId()) {
            return $product->getCustomerGroupId();
        }
        return Mage::getSingleton('customer/session')->getCustomerGroupId();
    }
    /**
     * Get product pricing value
     *
     * @param   array $value
     * @param   Mage_Catalog_Model_Product $product
     * @return  double
     */
    public function getPricingValue($value, $product, $qty = null)
    {
        if($value['is_percent']) {
            $ratio = $value['pricing_value']/100;
            $price = $this->_applyTierPrice($product, $qty, $product->getPrice());
            $price = $this->_applySpecialPrice($product, $price);
            $price = $price * $ratio;
        } else {
            $price = $value['pricing_value'];
        }

        return $price;
    }

    /**
     * Get product tier price by qty
     *
     * @param   double $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  double
     */
    public function getTierPrice($qty=null, $product)
    {
        $allGroups = Mage_Customer_Model_Group::CUST_GROUP_ALL;
        #$defaultGroup = Mage::getStoreConfig(Mage_Customer_Model_Group::XML_PATH_DEFAULT_ID);

        $prices = $product->getData('tier_price');
        /**
         * Load tier price
         */
        if (is_null($prices)) {
            if ($attribute = $product->getResource()->getAttribute('tier_price')) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('tier_price');
            }
        }

        if (is_null($prices) || !is_array($prices)) {
            if (!is_null($qty)) {
                return $product->getPrice();
            }
            return array(array(
                'price'         => $product->getPrice(),
                'website_price' => $product->getPrice(),
                'price_qty'     => 1,
                'cust_group'    => $allGroups,
            ));
        }

        $custGroup = $this->_getCustomerGroupId($product);
        if ($qty) {
            // starting with quantity 1 and original price
            $prevQty = 1;
            $prevPrice = $product->getPrice();
            $prevGroup = $allGroups;

            foreach ($prices as $price) {
                if ($price['cust_group']!=$custGroup && $price['cust_group']!=$allGroups) {
                    // tier not for current customer group nor is for all groups
                    continue;
                }
                if ($qty < $price['price_qty']) {
                    // tier is higher than product qty
                    continue;
                }
                if ($price['price_qty'] < $prevQty) {
                    // higher tier qty already found
                    continue;
                }
                if ($price['price_qty'] == $prevQty && $prevGroup != $allGroups && $price['cust_group'] == $allGroups) {
                    // found tier qty is same as current tier qty but current tier group is ALL_GROUPS
                    continue;
                }
                $prevPrice = $price['website_price'];
                $prevQty = $price['price_qty'];
                $prevGroup = $price['cust_group'];
            }
            return $prevPrice;
        } else {
            foreach ($prices as $i=>$price) {
                if ($price['cust_group']!=$custGroup && $price['cust_group']!=$allGroups) {
                    unset($prices[$i]);
                }
            }
        }

        return ($prices) ? $prices : array();
    }

    /**
     * Count how many tier prices we have for the product
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  int
     */
    public function getTierPriceCount($product)
    {
        $price = $product->getTierPrice();
        return count($price);
    }

    /**
     * Get formated by currency tier price
     *
     * @param   double $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  array || double
     */
    public function getFormatedTierPrice($qty=null, $product)
    {
        $price = $product->getTierPrice($qty);
        if (is_array($price)) {
            foreach ($price as $index => $value) {
                $price[$index]['formated_price'] = Mage::app()->getStore()->convertPrice($price[$index]['website_price'], true);
            }
        }
        else {
            $price = Mage::app()->getStore()->formatPrice($price);
        }

        return $price;
    }

    /**
     * Get formated by currency product price
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  array || double
     */
    public function getFormatedPrice($product)
    {
        return Mage::app()->getStore()->formatPrice($product->getFinalPrice());
    }

    /**
     * Get product final price
     *
     * @param double $qty
     * @param Mage_Catalog_Model_Product $product
     * @return double
     */
    public function getFinalPrice($qty=null, $product)
    {
        /**
         * Calculating final price for item of configurable product
         */
        if($product->getSuperProduct() && $product->getSuperProduct()->isConfigurable()) {
            $finalPrice = $product->getSuperProduct()->getFinalPrice($qty);
            $product->getSuperProduct()->getTypeInstance()->setStoreFilter($product->getStore());
            $attributes = $product->getSuperProduct()->getTypeInstance()->getConfigurableAttributes();
            foreach ($attributes as $attribute) {
                $value = $this->getValueByIndex(
                    $attribute->getPrices() ? $attribute->getPrices() : array(),
                    $product->getData($attribute->getProductAttribute()->getAttributeCode())
                );
                if($value) {
                    if($value['pricing_value'] != 0) {
                        $finalPrice += $product->getSuperProduct()->getPricingValue($value, $qty);
                    }
                }
            }
        }
        /**
         * Calculating final price of simple product
         */
        else {
            $finalPrice = $product->getPrice();

            $finalPrice = $this->_applyTierPrice($product, $qty, $finalPrice);

            $finalPrice = $this->_applySpecialPrice($product, $finalPrice);
        }

        $product->setFinalPrice($finalPrice);
        Mage::dispatchEvent('catalog_product_get_final_price', array('product'=>$product));
        return $product->getData('final_price');
    }

    /**
     * Get calculated product price
     *
     * @param array $options
     * @param Mage_Catalog_Model_Product $product
     * @return double
     */
    public function getCalculatedPrice(array $options, $product)
    {
        $price = $product->getPrice();
        foreach ($product->getSuperAttributes() as $attribute) {
            if(isset($options[$attribute['attribute_id']])) {
                if($value = $this->getValueByIndex($attribute['values'], $options[$attribute['attribute_id']])) {
                    if($value['pricing_value'] != 0) {
                        $price += $product->getPricingValue($value);
                    }
                }
            }
        }
        return $price;
    }

    public function getValueByIndex($values, $index)
    {
        return $this->_getValueByIndex($values, $index);
    }

    protected function _getValueByIndex($values, $index) {
        foreach ($values as $value) {
            if($value['value_index'] == $index) {
                return $value;
            }
        }
        return false;
    }

    /**
     * apply special price for product if not return
     * price that was before
     *
     */
    protected function _applySpecialPrice($product, $finalPrice)
    {
        $specialPrice = $product->getSpecialPrice();
        if (is_numeric($specialPrice)) {
            $today = floor(time()/86400)*86400;
            $from = floor(strtotime($product->getSpecialFromDate())/86400)*86400;
            $to = floor(strtotime($product->getSpecialToDate())/86400)*86400;

            if ($product->getSpecialFromDate() && $today < $from) {
            } elseif ($product->getSpecialToDate() && $today > $to) {
            } else {
               $finalPrice = min($finalPrice, $specialPrice);
            }
        }
        return $finalPrice;
    }

    /**
     * apply tier price for product if not return
     * price that was before
     *
     */
    protected function _applyTierPrice($product, $qty, $finalPrice)
    {
        $tierPrice  = $product->getTierPrice($qty);
        if (is_numeric($tierPrice)) {
            $finalPrice = min($finalPrice, $tierPrice);
        }
        return $finalPrice;
    }
}
