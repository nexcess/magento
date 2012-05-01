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
 * @package    Mage_Wishlist
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wishlist sidebar block
 *
 * @category   Mage
 * @package    Mage_Wishlist
 */

class Mage_Wishlist_Block_Customer_Sidebar extends Mage_Core_Block_Template
{
	protected  $_wishlist = null;

	public function getWishlistItems()
	{
		return $this->getWishlist()->getProductCollection();
	}

	public function getWishlist()
	{
        if(is_null($this->_wishlist)) {
            $this->_wishlist = Mage::getModel('wishlist/wishlist')
                ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());

            $collection = $this->_wishlist->getProductCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('small_image')
                ->addAttributeToSelect('thumbnail')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('tax_class_id')
                ->addAttributeToFilter('store_id', array('in'=>$this->_wishlist->getSharedStoreIds()))
                ->addAttributeToSort('added_at', 'desc')
                ->setCurPage(1)
                ->setPageSize(3)
                ->addUrlRewrite();
        }

        return $this->_wishlist;
    }

	protected function _toHtml()
	{
        if( sizeof($this->getWishlistItems()->getItems()) > 0 ){
        	return parent::_toHtml();
        } else {
            return '';
        }
	}

	public function getCanDisplayWishlist()
	{
		return Mage::getSingleton('customer/session')->isLoggedIn();
	}

	public function getRemoveItemUrl($item)
	{
	    return $this->getUrl('wishlist/index/remove',array('item'=>$item->getWishlistItemId()));
	}

	public function getAddToCartItemUrl($item)
	{
	    return Mage::helper('wishlist')->getAddToCartUrlBase64($item);
	}
}
