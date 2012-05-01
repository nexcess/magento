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
 * One page abstract child block
 *
 * @category   Mage
 * @category   Mage
 * @package    Mage_Checkout
 */
abstract class Mage_Checkout_Block_Cart_Abstract extends Mage_Core_Block_Template
{
    protected $_customer;
    protected $_checkout;
    protected $_quote;
    
    protected $_alnumFilter;
    protected $_priceFilter;
    protected $_qtyFilter;
    protected $_isWishlistActive;
    
    protected function _construct()
    {
        $this->_alnumFilter = new Zend_Filter_Alnum();
        $this->_priceFilter = Mage::app()->getStore()->getPriceFilter();
        $this->_qtyFilter = new Varien_Filter_Sprintf('%d');
        $this->_isWishlistActive = Mage::getStoreConfig('wishlist/general/active')
            && Mage::getSingleton('customer/session')->isLoggedIn();
            
        
        parent::_construct();
    }
    
    public function getCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }
    
    public function getCheckout()
    {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }
    
    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }
}