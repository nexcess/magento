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
 * Quote item model
 *
 * @category   Mage
 * @package    Mage_Sales
 */
class Mage_Sales_Model_Quote_Item extends Mage_Sales_Model_Quote_Item_Abstract
{
    /**
     * Quote model object
     *
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    function _construct()
    {
        $this->_init('sales/quote_item');
    }

    public function __destruct()
    {
        unset($this->_quote);
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        if ($this->getQuote()) {
            $this->setParentId($this->getQuote()->getId());
        }
        return $this;
    }

    /**
     * Declare quote model object
     *
     * @param   Mage_Sales_Model_Quote $quote
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        if ($this->getHasError()) {
            $quote->setHasError(true);
        }
        $quote->addMessage($this->getQuoteMessage(), $this->getQuoteMessageIndex());
        return $this;
    }

    /**
     * Retrieve quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    protected function _prepareQty($qty)
    {
        $qty = floatval($qty);
        $qty = ($qty > 0) ? $qty : 1;
        return $qty;
    }

    /**
     * Adding quantity to quote item
     *
     * @param   float $qty
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function addQty($qty)
    {
        $oldQty = $this->getQty();
        $qty = $this->_prepareQty($qty);
        $this->setQty($oldQty+$qty);
        return $this;
    }

    /**
     * Declare quote item quantity
     *
     * @param   float $qty
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function setQty($qty)
    {
        $qty    = $this->_prepareQty($qty);
        $oldQty = $this->getQty();
        $this->setData('qty', $qty);

        Mage::dispatchEvent('sales_quote_item_qty_set_after', array('item'=>$this));

        if ($this->getQuote() && $this->getQuote()->getIgnoreOldQty()) {
            return $this;
        }
        if ($this->getUseOldQty()) {
            $this->setData('qty', $oldQty);
        }
        return $this;
    }

    /**
     * Retrieve product model object associated with item
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->hasData('product') && $this->getProductId()) {
            $this->setProduct(Mage::getModel('catalog/product')->load($this->getProductId()));
        }
        return $this->getData('product');
    }

    public function toArray(array $arrAttributes=array())
    {
        $data = parent::toArray($arrAttributes);

        if ($product = $this->getProduct()) {
            $data['product'] = $product->toArray();
        }
        if ($superProduct = $this->getSuperProduct()) {
            $data['super_product'] = $superProduct->toArray();
        }

        return $data;
    }
}
