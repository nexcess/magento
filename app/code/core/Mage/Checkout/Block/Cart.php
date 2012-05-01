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

class Mage_Checkout_Block_Cart extends Mage_Checkout_Block_Cart_Abstract
{

    protected $_totals;

    public function chooseTemplate()
    {
        if ($this->getQuote()->hasItems()) {
            $this->setTemplate($this->getCartTemplate());
        } else {
            $this->setTemplate($this->getEmptyTemplate());
        }
    }

    public function hasError()
    {
        return $this->getQuote()->getHasError();
    }

    public function getItems()
    {
        return $this->getQuote()->getAllItems();
    }

    public function getItemsSummaryQty()
    {
        return $this->getQuote()->getItemsSummaryQty();
    }

    public function getTotals()
    {
        return $this->getTotalsCache();
    }

    public function getTotalsCache()
    {
        if (empty($this->_totals)) {
            $this->_totals = $this->getQuote()->getTotals();
        }
        return $this->_totals;
    }

    public function getGiftcertCode()
    {
        return $this->getQuote()->getGiftcertCode();
    }

    public function isWishlistActive()
    {
        return $this->_isWishlistActive;
    }

    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout/onepage', array('_secure'=>true));
    }

    public function getItemProduct(Mage_Sales_Model_Quote_Item $item)
    {
        return $this->helper('checkout')->getQuoteItemProduct($item);
    }
    public function getItemProductForThumbnail(Mage_Sales_Model_Quote_Item $item)
    {
        return $this->helper('checkout')->getQuoteItemProductThumbnail($item);
    }

    public function getItemDeleteUrl(Mage_Sales_Model_Quote_Item $item)
    {
        return $this->getUrl('checkout/cart/delete', array('id'=>$item->getId()));
    }

    public function getItemUrl($item)
    {
        return $this->helper('checkout')->getQuoteItemProductUrl($item);
    }

    public function getItemName($item)
    {
        return $this->helper('checkout')->getQuoteItemProductName($item);
    }

    public function getItemDescription($item)
    {
        return $this->helper('checkout')->getQuoteItemProductDescription($item);
    }

    public function getItemQty($item)
    {
        return $this->helper('checkout')->getQuoteItemQty($item);
    }

    public function getItemIsInStock($item)
    {
        return $this->helper('checkout')->getQuoteItemProductIsInStock($item);
    }

    public function getContinueShoppingUrl()
    {
        $url = $this->getData('continue_shopping_url');
        if (is_null($url)) {
            $url = Mage::getSingleton('checkout/session')->getContinueShoppingUrl(true);
            if (!$url) {
                $url = Mage::getUrl();
            }
            $this->setData('continue_shopping_url', $url);
        }
        return $url;
    }

    public function getIncExcTax($flag)
    {
        $text = Mage::helper('tax')->getIncExcText($flag);
        return $text ? ' ('.$text.')' : '';
    }
}
