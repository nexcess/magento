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
 * Shoping cart model
 *
 * @category   Mage
 * @package    Mage_Checkout
 */
class Mage_Checkout_Model_Cart extends Varien_Object
{
    protected $_cacheKey;
    protected $_cacheData;
    protected $_summaryQty;
    protected $_productIds;

    protected function _getResource()
    {
        return Mage::getResourceSingleton('checkout/cart');
    }

    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Retrieve custome session model
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function getItems()
    {
        if (!$this->getQuote()->getId()) {
            return array();
        }
        return $this->getQuote()->getItemsCollection();
    }


    public function getItemsCount()
    {
        return $this->getQuote()->getItemsCount();
    }

    public function getItemsQty()
    {
        return $this->getQuote()->getItemsQty();
    }

    /**
     * Retrieve array of cart product ids
     *
     * @return array
     */
    public function getQuoteProductIds()
    {
        $products = $this->getData('product_ids');
        if (is_null($products)) {
            $products = array();
            foreach ($this->getQuote()->getAllItems() as $item) {
            	$products[$item->getProductId()] = $item->getProductId();
            }
            $this->setData('product_ids', $products);
        }
        return $products;
    }

    public function getCustomerWishlist()
    {
        $wishlist = $this->getData('customer_wishlist');
        if (is_null($wishlist)) {
            $wishlist = false;
            if ($customer = $this->getCustomerSession()->getCustomer()) {
                $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);
            }
            $this->setData('customer_wishlist', $wishlist);
        }
        return $wishlist;
    }

    /**
     * Retrieve current quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckoutSession()->getQuote();
    }

    public function init()
    {
        $this->getQuote()->setCheckoutMethod('');

        /**
         * If user try do checkout, reset shipiing and payment data
         */
        if ($this->getCheckoutSession()->getCheckoutState() !== Mage_Checkout_Model_Session::CHECKOUT_STATE_BEGIN) {
        	$this->getQuote()
        		->removeAllAddresses()
        		->removePayment();
            $this->getCheckoutSession()->resetCheckout();
        }

        if (!$this->getQuote()->hasItems()) {
        	$this->getQuote()->getShippingAddress()
        		->setCollectShippingRates(false)
        		->removeAllShippingRates();
        }

        return $this;
    }

    public function addOrderItem($orderItem)
    {
        $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
        if (!$product->getId()) {
            return $this;
        }
        if ($orderItem->getSuperProductId()) {
            $superProduct = Mage::getModel('catalog/product')->load($orderItem->getSuperProductId());
            if (!$superProduct->getId()) {
                return $this;
            }
            $product->setSuperProduct($superProduct);
        }
        $this->getQuote()->addCatalogProduct($product, $orderItem->getQtyOrdered());
        return $this;
    }

    /**
     * Add products
     *
     * @param   int $productId
     * @param   int $qty
     * @return  Mage_Checkout_Model_Cart
     */
    public function addProduct($product, $qty=1)
    {
        $item = false;
        if ($product->getId() && $product->isVisibleInCatalog()) {
            switch ($product->getTypeId()) {
                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                    $item = $this->_addConfigurableProduct($product, $qty);
                    break;
                case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                    $item = $this->_addGroupedProduct($product, $qty);
                    break;
                default:
                    $item = $this->_addProduct($product, $qty);
                    break;
            }
        }
        else {
            Mage::throwException(Mage::helper('checkout')->__('Product does not exist'));
        }
        /**
         * $item can be false, array and Mage_Sales_Model_Quote_Item
         */
        Mage::dispatchEvent('checkout_cart_product_add_after', array('quote_item'=>$item, 'product'=>$product));
        $this->getCheckoutSession()->setLastAddedProductId($product->getId());
        return $this;
    }

    /**
     * Adding simple product to shopping cart
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   int $qty
     * @return  Mage_Checkout_Model_Cart
     */
    protected function _addProduct(Mage_Catalog_Model_Product $product, $qty)
    {
        $item = $this->getQuote()->addCatalogProduct($product, $qty);
        if ($item->getHasError()) {
            $this->setLastQuoteMessage($item->getQuoteMessage());
            Mage::throwException($item->getMessage());
        }
        return $item;
    }

    /**
     * Adding grouped product to cart
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Checkout_Model_Cart
     */
    protected function _addGroupedProduct(Mage_Catalog_Model_Product $product)
    {
        $groupedProducts = $product->getGroupedProducts();

        if(!is_array($groupedProducts) || empty($groupedProducts)) {
            $this->getCheckoutSession()->setRedirectUrl($product->getProductUrl());
            $this->getCheckoutSession()->setUseNotice(true);
            Mage::throwException(Mage::helper('checkout')->__('Please specify the product option(s)'));
        }

        $added = false;
        $items = array();
        foreach($product->getTypeInstance()->getAssociatedProducts() as $subProduct) {
            if(isset($groupedProducts[$subProduct->getId()])) {
                $qty =  $groupedProducts[$subProduct->getId()];
                if (!empty($qty)) {
                    $subProduct->setSuperProduct($product);
                    $items[] = $this->getQuote()->addCatalogProduct($subProduct, $qty);
                    $added = true;
                }
            }
        }
        if (!$added) {
            Mage::throwException(Mage::helper('checkout')->__('Please specify the product(s) quantity'));
        }
        return $items;
    }

    /**
     * Adding configurable product
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Checkout_Model_Cart
     */
    protected function _addConfigurableProduct(Mage_Catalog_Model_Product $product, $qty=1)
    {
        if($product->getConfiguredAttributes()) {
            $subProduct = $product->getTypeInstance()->getProductByAttributes(
                $product->getConfiguredAttributes()
            );
        } else {
            $subProduct = false;
        }
        $item = false;
        if($subProduct) {
            $subProduct->setSuperProduct($product);
            $item = $this->getQuote()->addCatalogProduct($subProduct, $qty);
            if ($item->getHasError()) {
                $this->setLastQuoteMessage($item->getQuoteMessage());
                Mage::throwException($item->getMessage());
            }
        }
        else {
            $this->getCheckoutSession()->setRedirectUrl($product->getProductUrl());
            $this->getCheckoutSession()->setUseNotice(true);
            Mage::throwException(Mage::helper('checkout')->__('Please specify the product option(s)'));
        }
        return $item;
    }

    /**
     * Adding products to cart by ids
     *
     * @param   array $productIds
     * @return  Mage_Checkout_Model_Cart
     */
    public function addProductsByIds($productIds)
    {
        $allAvailable = true;
        $allAdded     = true;

        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                $product = Mage::getModel('catalog/product')
                    ->load($productId);
                if ($product->getId() && $product->isVisibleInCatalog()) {
                    try {
                        $this->getQuote()->addCatalogProduct($product);
                    }
                    catch (Exception $e){
                        $allAdded = false;
                    }
                }
                else {
                    $allAvailable = false;
                }
            }

            if (!$allAvailable) {
                $this->getCheckoutSession()->addError(
                Mage::helper('checkout')->__('Some of the products you requested are unavailable')
                );
            }
            if (!$allAdded) {
                $this->getCheckoutSession()->addError(
                Mage::helper('checkout')->__('Some of the products you requested are not available in the desired quantity')
                );
            }
        }
        return $this;
    }

    /**
     * Update cart items
     *
     * @param   array $data
     * @return  Mage_Checkout_Model_Cart
     */
    public function updateItems($data)
    {
        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

        	if (!empty($itemInfo['remove']) || (isset($itemInfo['qty']) && $itemInfo['qty']=='0')) {
        	    $this->removeItem($itemId);
        	    continue;
        	}

        	if (!empty($itemInfo['wishlist'])) {
        	    $this->moveItemToWishlist($itemId);
        	    continue;
        	}

            $qty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;
        	if ($qty > 0) {
        	    $item->setQty($qty);
        	}
        }
        return $this;
    }

    /**
     * Remove item from cart
     *
     * @param   int $itemId
     * @return  Mage_Checkout_Model_Cart
     */
    public function removeItem($itemId)
    {
        $this->getQuote()->removeItem($itemId);
        return $this;
    }

    /**
     * Move cart item to wishlist
     *
     * @param   int $itemId
     * @return  Mage_Checkout_Model_Cart
     */
    public function moveItemToWishlist($itemId)
    {
        if ($wishlist = $this->getCustomerWishlist()) {
            if ($item = $this->getQuote()->getItemById($itemId)) {
                $productId = $item->getProductId();
                if ($item->getSuperProductId()) {
                    $productId = $item->getSuperProductId();
                }
                $wishlist->addNewItem($productId)
                    ->save();
                $this->removeItem($itemId);
            }
        }
        return $this;
    }

    /**
     * Save cart
     *
     * @return Mage_Checkout_Model_Cart
     */
    public function save()
    {
        $address = $this->getQuote()->getShippingAddress();
        $total = $address->getGrandTotal();
        $address->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();
        /*if ($total!=$address->getGrandTotal()) {
            $this->getQuote()->save();
        }*/
        $this->getCheckoutSession()->setQuoteId($this->getQuote()->getId());
        return $this;
    }

    public function truncate()
    {
        foreach ($this->getQuote()->getItemsCollection() as $item) {
            $item->isDeleted(true);
        }
    }

    public function getCartInfo($quoteId=null)
    {
        $store = Mage::app()->getStore();
        if (is_null($quoteId)) {
            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        }

        $cacheKey = 'CHECKOUT_QUOTE'.$quoteId.'_STORE'.$store->getId();
        if (Mage::app()->useCache('checkout_quote') && $cache = Mage::app()->loadCache($cacheKey)) {
            return unserialize($cache);
        }

        $cart = array('items'=>array(), 'subtotal'=>0);
        $cacheTags = array('checkout_quote', 'catalogrule_product_price', 'checkout_quote_'.$quoteId);

        if ($this->getSummaryQty($quoteId)>0) {

            $itemsArr = $this->_getResource()->fetchItems($quoteId);
            $productIds = array();
            foreach ($itemsArr as $item) {
                $productIds[] = $item['product_id'];
                if (!empty($item['super_product_id'])) {
                    $productIds[] = $item['super_product_id'];
                }
            }

            $productIds = array_unique($productIds);
            foreach ($productIds as $id) {
                $cacheTags[] = 'catalog_product_'.$id;
            }

            $products = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addMinimalPrice()
                ->addStoreFilter()
                ->addIdFilter($productIds);


            foreach ($itemsArr as $it) {
                $product = $products->getItemById($it['product_id']);
                if (!$product) {
                    continue;
                }

                $item = new Varien_Object($it);
                $item->setProduct($product);

                $superProduct = null;
                if (!empty($it['super_product_id'])) {
                    $superProduct = $products->getItemById($it['super_product_id']);
                    $item->setSuperProduct($superProduct);
                    $product->setProduct($product);
                    $product->setSuperProduct($superProduct);
                }
                $item->setProductName(!empty($superProduct) ? $superProduct->getName() : $product->getName());
                $item->setProductUrl(!empty($superProduct) ? $superProduct->getProductUrl() : $product->getProductUrl());
                $item->setPrice($product->getFinalPrice($it['qty']));

                $thumbnailObjOrig = Mage::helper('checkout')->getQuoteItemProductThumbnail($item);
                $thumbnailObj = Mage::getModel('catalog/product');
                foreach ($thumbnailObjOrig->getData() as $k=>$v) {
                    if (is_scalar($v)) {
                        $thumbnailObj->setData($k, $v);
                    }
                }
                $item->setThumbnailObject($thumbnailObj);

                $item->setProductDescription(Mage::helper('catalog/product')->getProductDescription($product));

                $item->unsProduct()->unsSuperProduct();

                $cart['items'][] = $item;

                $cart['subtotal'] += $item->getPrice()*$item->getQty();
            }
        }

        $cartObj = new Varien_Object($cart);
        if (Mage::app()->useCache('checkout_quote')) {
            Mage::app()->saveCache(serialize($cartObj), $cacheKey, $cacheTags);
        }

        return $cartObj;
    }

    public function getProductIds($quoteId=null)
    {
        if (is_null($quoteId)) {
            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        }

        if (!isset($this->_productIds[$quoteId])) {
    	    $productIds = array();
    	    if ($this->getSummaryQty()>0) {
    	       foreach ($this->getCartInfo($quoteId)->getItems() as $item) {
    	           $productIds[] = $item->getProductId();
    	       }
    	    }
    	    $this->_productIds[$quoteId] = array_unique($productIds);
        }
	    return $this->_productIds[$quoteId];
    }

    public function getSummaryQty($quoteId=null)
    {
        if (is_null($quoteId)) {
            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        }
        if (!isset($this->_summaryQty[$quoteId])) {
            $summary = $this->_getResource()->fetchItemsSummary($quoteId);
            if (Mage::getStoreConfig('checkout/cart_link/use_qty')) {
                $this->_summaryQty[$quoteId] = $summary['items_qty']*1;
            }
            else {
                $this->_summaryQty[$quoteId] = $summary['items_count']*1;
            }
        }
        return $this->_summaryQty[$quoteId];
    }
}