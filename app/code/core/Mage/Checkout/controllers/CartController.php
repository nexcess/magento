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


class Mage_Checkout_CartController extends Mage_Core_Controller_Front_Action
{

    protected function _goBack()
    {
        if (!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
            && !$this->getRequest()->getParam('in_cart')
            && $backUrl = $this->_getRefererUrl()) {

            $this->getResponse()->setRedirect($backUrl);
        } else {
            if (($this->getRequest()->getActionName() == 'add') && !$this->getRequest()->getParam('in_cart')) {
                Mage::getSingleton('checkout/session')->setContinueShoppingUrl($this->_getRefererUrl());
            }
            $this->_redirect('checkout/cart');
        }
        return $this;
    }

    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = Mage::getSingleton('checkout/session')->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    public function indexAction()
    {
        Varien_Profiler::start('TEST1: '.__METHOD__);
        $this->getQuote()->setCacheKey(false); // cache is not used for cart page
        Varien_Profiler::stop('TEST1: '.__METHOD__);
        Varien_Profiler::start('TEST2: '.__METHOD__);
        $cart = $this->_getCart();
        Varien_Profiler::stop('TEST2: '.__METHOD__);
        Varien_Profiler::start('TEST3: '.__METHOD__);
        $cart->init();
        Varien_Profiler::stop('TEST3: '.__METHOD__);
        Varien_Profiler::start('TEST4: '.__METHOD__);
        $cart->save();
        Varien_Profiler::stop('TEST4: '.__METHOD__);
        Varien_Profiler::start('TEST5: '.__METHOD__);

        foreach ($cart->getQuote()->getMessages() as $message) {
            if ($message) {
                $cart->getCheckoutSession()->addMessage($message);
            }
        }

        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        $this->_initLayoutMessages('catalog/session');

        $this->renderLayout();
        Varien_Profiler::stop('TEST5: '.__METHOD__);
    }

    public function addgroupAction()
    {
        $productIds = $this->getRequest()->getParam('products');
        if (is_array($productIds)) {
            $cart = $this->_getCart();
            $cart->addProductsByIds($productIds);
            $cart->save();
        }
        $this->_goBack();
    }

    /**
     * Adding product to shopping cart action
     */
    public function addAction()
    {
        $productId       = (int) $this->getRequest()->getParam('product');
        $qty             = (float) $this->getRequest()->getParam('qty', 1);
        $relatedProducts = (string) $this->getRequest()->getParam('related_product');

        if (!$productId) {
            $this->_goBack();
            return;
        }

        $additionalIds = array();
        /**
         * Parse related products
         */
        if ($relatedProducts) {
            $relatedProducts = explode(',', $relatedProducts);
            if (is_array($relatedProducts)) {
                foreach ($relatedProducts as $relatedId) {
                    $additionalIds[] = $relatedId;
                }
            }
        }

        try {
            $cart = $this->_getCart();
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId)
                ->setConfiguredAttributes($this->getRequest()->getParam('super_attribute'))
                ->setGroupedProducts($this->getRequest()->getParam('super_group', array()));
            $eventArgs = array(
                'product' => $product,
                'qty' => $qty,
                'additional_ids' => $additionalIds,
                'request' => $this->getRequest(),
                'response' => $this->getResponse(),
            );

            Mage::dispatchEvent('checkout_cart_before_add', $eventArgs);

            $cart->addProduct($product, $qty)
                ->addProductsByIds($additionalIds);

            Mage::dispatchEvent('checkout_cart_after_add', $eventArgs);

            $cart->save();

            Mage::dispatchEvent('checkout_cart_add_product', array('product'=>$product));

            $message = $this->__('%s was successfully added to your shopping cart.', $product->getName());

            if (!Mage::getSingleton('checkout/session')->getNoCartRedirect(true)) {
                Mage::getSingleton('checkout/session')->addSuccess($message);
                $this->_goBack();
            }
        }
        catch (Mage_Core_Exception $e) {
            if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
            }
            else {
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
            }

            $url = Mage::getSingleton('checkout/session')->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            }
            else {
                $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addException($e, $this->__('Can not add item to shopping cart'));
            $this->_goBack();
        }
    }

    /**
     * Update shoping cart data action
     */
    public function updatePostAction()
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $cart = $this->_getCart();
                $cart->updateItems($cartData)
                    ->save();
            }
            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        }
        catch (Mage_Core_Exception $e){
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }
        catch (Exception $e){
            Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot update shopping cart'));
        }
        $this->_goBack();
    }

    /**
     * Move shopping cart item to wishlist action
     */
    public function moveToWishlistAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->_getCart()->moveItemToWishlist($id)
                    ->save();
            }
            catch (Exception $e){
                Mage::getSingleton('checkout/session')->addError($this->__('Cannot move item to wishlist'));
            }
        }
        $this->_goBack();
    }

    /**
     * Delete shoping cart item action
     */
    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->_getCart()->removeItem($id)
                  ->save();
            } catch (Exception $e) {
                Mage::getSingleton('checkout/session')->addError($this->__('Cannot remove item'));
            }
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
    }

    public function estimatePostAction()
    {
        $country    = (string) $this->getRequest()->getParam('country_id');
        $postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
        $city       = (string) $this->getRequest()->getParam('estimate_city');
        $regionId   = (string) $this->getRequest()->getParam('region_id');
        $region     = (string) $this->getRequest()->getParam('region');

        $this->getQuote()->getShippingAddress()
            ->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($regionId)
            ->setRegion($region)
            ->setCollectShippingRates(true);
        $this->getQuote()->save();
        $this->_goBack();
    }

    public function estimateUpdatePostAction()
    {
        $code = (string) $this->getRequest()->getParam('estimate_method');
        if (!empty($code)) {
            $this->getQuote()->getShippingAddress()->setShippingMethod($code)/*->collectTotals()*/->save();
        }
        $this->_goBack();
    }

    public function couponPostAction()
    {
        $couponCode = (string) $this->getRequest()->getParam('coupon_code');
        if (!strlen($couponCode)) {
            $this->_goBack();
            return;
        }

        try {
            $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
            $this->getQuote()->setCouponCode($couponCode)
                ->collectTotals()
                ->save();
            if ($couponCode) {
                if ($couponCode == $this->getQuote()->getShippingAddress()->getCouponCode()) {
                    Mage::getSingleton('checkout/session')->addSuccess(
                        $this->__('Coupon code was applied successfully.')
                    );
                }
                else {
                    Mage::getSingleton('checkout/session')->addError(
                        $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode))
                    );
                }
            }

        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError(
                $this->__('Can not apply coupon code.')
            );
        }

        $this->_goBack();
    }
}