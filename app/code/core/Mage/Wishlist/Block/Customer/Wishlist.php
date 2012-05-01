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
 * Wishlist block customer items
 *
 * @category   Mage
 * @package    Mage_Wishlist
 */
class Mage_Wishlist_Block_Customer_Wishlist extends Mage_Core_Block_Template
{

    protected $_wishlistLoaded = false;

    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('My Wishlist'));
        }
    }

    public function getWishlist()
    {
        if(!$this->_wishlistLoaded) {
            Mage::registry('wishlist')
                ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());
            $collection = Mage::registry('wishlist')->getProductCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('thumbnail')
                ->addAttributeToSelect('small_image')
                ->addAttributeToSelect('tax_class_id')
                ->addAttributeToFilter('store_id', array('in'=>Mage::registry('wishlist')->getSharedStoreIds()))
                ->addStoreFilter();




            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

            $this->_wishlistLoaded = true;
        }

        return Mage::registry('wishlist')->getProductCollection();
    }

    public function getEscapedDescription(Varien_Object $item)
    {
        return $this->htmlEscape($item->getDescription());
    }

    public function getFormatedDate($date)
    {
        return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
    }

    public function getItemAddToCartUrl($item)
    {
        return $this->getUrl('*/*/cart',array('item'=>$item->getWishlistItemId()));
    }

    public function getItemRemoveUrl($item)
    {
        return $this->getUrl('*/*/remove',array('item'=>$item->getWishlistItemId()));
    }

    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    public function isSaleable()
    {
        foreach ($this->getWishlist() as $item) {
            if ($item->isSaleable()) {
                return true;
            }
        }

        return false;
    }
}
