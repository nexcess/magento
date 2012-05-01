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

/**
 * Checkout default helper
 *
 */
class Mage_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{
    const GROUPED_PRODUCT_IMAGE     = 'checkout/cart/grouped_product_image';
    const CONFIGURABLE_PRODUCT_IMAGE= 'checkout/cart/configurable_product_image';
    const USE_PARENT_IMAGE = 'parent';
    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getQuoteItemProduct(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $superProduct = $item->getSuperProduct();
        if ($superProduct) {
            $product = $superProduct;
        } else {
            $product = $item->getProduct();
        }

        return $product;
    }

    public function getQuoteItemProductThumbnail($item)
    {
        $superProduct   = $item->getSuperProduct();
        $product        = $item->getProduct();
        if ($superProduct) {
            if ($product->getData('thumbnail') == 'no_selection') {
                $product = $superProduct;
            }
            elseif ($superProduct->isConfigurable()
                    && Mage::getStoreConfig(self::CONFIGURABLE_PRODUCT_IMAGE) == self::USE_PARENT_IMAGE) {
                $product = $superProduct;
            }
            elseif ($superProduct->isGrouped()
                    && Mage::getStoreConfig(self::GROUPED_PRODUCT_IMAGE) == self::USE_PARENT_IMAGE) {
                $product = $superProduct;
            }
        }

        return $product;
    }

    /**
     * Retrieve quote item product url
     *
     * @param   Mage_Sales_Model_Quote_Item $item
     * @return  string
     */
    public function getQuoteItemProductUrl($item)
    {
        return $this->getQuoteItemProduct($item)->getProductUrl();
    }

    /**
     * Retrieve quote item product name
     *
     * @param   Mage_Sales_Model_Quote_Item $item
     * @return  string
     */
    public function getQuoteItemProductName($item)
    {
        $product = $this->getQuoteItemProduct($item);
        if ($product && !$product->isGrouped()) {
            return $product->getName();
        }
        return $item->getName();
    }

    /**
     * Retrieve quote item product description
     *
     * @param   Mage_Sales_Model_Quote_Item $item
     * @return  string
     */
    public function getQuoteItemProductDescription($item)
    {
        if ($superProduct = $item->getSuperProduct()) {
            if ($superProduct->isConfigurable()) {
                return $this->_getConfigurableProductDescription($item->getProduct());
            }
        }
        return '';
    }

    /**
     * Retrieve quote item qty
     *
     * @param   Mage_Sales_Model_Quote_Item $item
     * @return  int || float
     */
    public function getQuoteItemQty($item)
    {
        return $item->getQty()*1;
    }

    /**
     * Retrieve quote item product in stock flag
     *
     * @param   Mage_Sales_Model_Quote_Item $item
     * @return  bool
     */
    public function getQuoteItemProductIsInStock($item)
    {
        if ($item->getProduct()->isSaleable()) {
            if ($item->getProduct()->getQty()>=$item->getQty()) {
                return true;
            }
        }
        return false;
    }

    protected function _getConfigurableProductDescription($product)
    {
         $html = '<ul class="super-product-attributes">';
         $attributes = $product->getSuperProduct()->getTypeInstance()->getUsedProductAttributes();
         foreach ($attributes as $attribute) {
             $html.= '<li><strong>' . $attribute->getFrontend()->getLabel() . ':</strong> ';
             if($attribute->getSourceModel()) {
                 $html.= $this->htmlEscape(
                    $attribute->getSource()->getOptionText($product->getData($attribute->getAttributeCode()))
                );
             } else {
                 $html.= $this->htmlEscape($product->getData($attribute->getAttributeCode()));
             }
             $html.='</li>';
         }
         $html.='</ul>';
         return $html;
    }

    public function formatPrice($price)
    {
        return $this->getQuote()->getStore()->formatPrice($price);
    }

    public function convertPrice($price, $format=true)
    {
        return $this->getQuote()->getStore()->convertPrice($price, $format);
    }
}
