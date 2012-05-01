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

/**
 * Product view abstract block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Mage_Catalog_Block_Product_View_Abstract extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Retrieve currently viewed product object
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', Mage::registry('current_product'));
        }
        return $this->getData('product');
    }

    public function getTierPrices($product = null)
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }
        $prices  = $product->getFormatedTierPrice();

        $res = array();
        if (is_array($prices)) {
            foreach ($prices as $price) {
                $price['price_qty'] = $price['price_qty']*1;
                if ($product->getPrice() != $product->getFinalPrice()) {
                    if ($price['price']<$product->getFinalPrice()) {
                        $price['savePercent'] = ceil(100 - (( 100/$product->getFinalPrice() ) * $price['price'] ));
                        $price['formated_price'] = Mage::app()->getStore()->formatPrice(Mage::app()->getStore()->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'])));
                        $price['formated_price_incl_tax'] = Mage::app()->getStore()->formatPrice(Mage::app()->getStore()->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'], true)));
                        $res[] = $price;
                    }
                }
                else {
                    if ($price['price']<$product->getPrice()) {
                        $price['savePercent'] = ceil(100 - (( 100/$product->getPrice() ) * $price['price'] ));
                        $price['formated_price'] = Mage::app()->getStore()->formatPrice(Mage::app()->getStore()->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'])));
                        $price['formated_price_incl_tax'] = Mage::app()->getStore()->formatPrice(Mage::app()->getStore()->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'], true)));
                        $res[] = $price;
                    }
                }
            }
        }

        return $res;
    }
}
