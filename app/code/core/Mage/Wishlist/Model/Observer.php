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
 * Shopping cart operation observer
 *
 */
class Mage_Wishlist_Model_Observer extends Mage_Core_Model_Abstract
{
    public function processAddToCart($observer)
    {
        $request = $observer->getRequest();
        $sharedWishlist = Mage::getSingleton('checkout/session')->getSharedWishlist();
        $messages = Mage::getSingleton('checkout/session')->getWishlistPendingMessages();
        $urls = Mage::getSingleton('checkout/session')->getWishlistPendingUrls();
        $wishlistIds = Mage::getSingleton('checkout/session')->getWishlistIds();
        $singleWishlistId = Mage::getSingleton('checkout/session')->getSingleWishlistId();

        if ($singleWishlistId) {
            $wishlistIds = array($singleWishlistId);
        }

        if (count($wishlistIds) && $request->getParam('wishlist_next')){
            $wishlistId = array_shift($wishlistIds);

            if (Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                $wishlist = Mage::getModel('wishlist/wishlist')
                        ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
            } else if ($sharedWishlist) {
                $wishlist = Mage::getModel('wishlist/wishlist')->loadByCode($sharedWishlist);
            } else {
                return;
            }


            $wishlist->getItemCollection()->load();

            foreach($wishlist->getItemCollection() as $wishlistItem){
                if ($wishlistItem->getId() == $wishlistId)
                    $wishlistItem->delete();
            }
            Mage::getSingleton('checkout/session')->setWishlistIds($wishlistIds);
            Mage::getSingleton('checkout/session')->setSingleWishlistId(null);
        }

        if ($request->getParam('wishlist_next') && count($urls)) {
            $url = array_shift($urls);
            $message = array_shift($messages);

            Mage::getSingleton('checkout/session')->setWishlistPendingUrls($urls);
            Mage::getSingleton('checkout/session')->setWishlistPendingMessages($messages);

            Mage::getSingleton('checkout/session')->addError($message);

            $observer->getResponse()->setRedirect($url);
            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);
        }
    }
}