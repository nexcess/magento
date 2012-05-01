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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order create model
 *
 */
class Mage_Adminhtml_Model_Sales_Order_Create extends Varien_Object
{
    /**
     * Quote session object
     *
     * @var Mage_Adminhtml_Model_Session_Quote
     */
    protected $_session;

    /**
     * Quote customer wishlist model object
     *
     * @var Mage_Wishlist_Model_Wishlist
     */
    protected $_wishlist;
    protected $_cart;
    protected $_compareList;

    protected $_needCollect;

    public function __construct()
    {
        $this->_session = Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Retrieve quote item
     *
     * @param   mixed $item
     * @return  Mage_Sales_Model_Quote_Item
     */
    protected function _getQuoteItem($item)
    {
        if ($item instanceof Mage_Sales_Model_Quote_Item) {
            return $item;
        }
        elseif (is_numeric($item)) {
            return $this->getSession()->getQuote()->getItemById($item);
        }
        return false;
    }

    /**
     * Initialize data for prise rules
     *
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function initRuleData()
    {
        Mage::register('rule_data', new Varien_Object(array(
            'store_id'  => $this->_session->getStore()->getId(),
            'customer_group_id' => $this->getCustomerGroupId(),
        )));
        return $this;
    }

    /**
     * Set collect totals flag for quote
     *
     * @param   bool $flag
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function setRecollect($flag)
    {
        $this->_needCollect = $flag;
        return $this;
    }

    /**
     * Quote saving
     *
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function saveQuote()
    {
        if (!$this->getQuote()->getId()) {
            return $this;
        }

        if ($this->_needCollect) {
            $this->getQuote()->collectTotals();
        }
        $this->getQuote()->save();
        return $this;
    }

    /**
     * Retrieve session model object of quote
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    public function getSession()
    {
        return $this->_session;
    }

    /**
     * Retrieve quote object model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getSession()->getQuote();
    }

    /**
     * Initialize creation data from existing order
     *
     * @param Mage_Sales_Model_Order $order
     * @return unknown
     */
    public function initFromOrder(Mage_Sales_Model_Order $order)
    {
        if (!$order->getReordered()) {
            $this->getSession()->setOrderId($order->getId());
        } else {
            $this->getSession()->setReordered($order->getId());
        }

        $this->getSession()->setCurrencyId($order->getOrderCurrencyCode());
        $this->getSession()->setCustomerId($order->getCustomerId());
        $this->getSession()->setStoreId($order->getStoreId());

        $convertModel = Mage::getModel('sales/convert_order');
        /*@var $quote Mage_Sales_Model_Quote*/
        $quote = $convertModel->toQuote($order, $this->getQuote());
        $quote->setShippingAddress($convertModel->toQuoteShippingAddress($order));
        $quote->setBillingAddress($convertModel->addressToQuoteAddress($order->getBillingAddress()));

        if ($order->getReordered()) {
            $quote->getPayment()->setMethod($order->getPayment()->getMethod());
        }
        else {
            $convertModel->paymentToQuotePayment($order->getPayment(), $quote->getPayment());
        }

        foreach ($order->getItemsCollection() as $item) {
            if ($order->getReordered()) {
                $qty = $item->getQtyOrdered();
            }
            else {
                $qty = min($item->getQtyToInvoice(), $item->getQtyToShip());
            }
            if ($qty) {
                $quoteItem = $convertModel->itemToQuoteItem($item)
                    ->setQty($qty);
                $product = $quoteItem->getProduct();

                if ($product->getId()) {
                    $quote->addItem($quoteItem);
                }
            }
        }

        if ($quote->getCouponCode()) {
            $quote->collectTotals();
        }

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->collectTotals();
        $quote->save();

        return $this;
    }

    /**
     * Retrieve customer wishlist model object
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    public function getCustomerWishlist()
    {
        if (!is_null($this->_wishlist)) {
            return $this->_wishlist;
        }

        if ($this->getSession()->getCustomer()->getId()) {
            $this->_wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(
                $this->getSession()->getCustomer(), true
            );
            $this->_wishlist->setStore($this->getSession()->getStore());
        }
        else {
            $this->_wishlist = false;
        }
        return $this->_wishlist;
    }

    /**
     * Retrieve customer cart quote object model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getCustomerCart()
    {
        if (!is_null($this->_cart)) {
            return $this->_cart;
        }

        $this->_cart = Mage::getModel('sales/quote');

        if ($this->getSession()->getCustomer()->getId()) {
            $this->_cart->setStore($this->getSession()->getStore())
                ->loadByCustomer($this->getSession()->getCustomer()->getId());
            if (!$this->_cart->getId()) {
                $this->_cart->assignCustomer($this->getSession()->getCustomer());
                $this->_cart->save();
            }
        }

        return $this->_cart;
    }

    /**
     * Retrieve customer compare list model object
     *
     * @return Mage_Catalog_Model_Product_Compare_List
     */
    public function getCustomerCompareList()
    {
        if (!is_null($this->_compareList)) {
            return $this->_compareList;
        }

        if ($this->getSession()->getCustomer()->getId()) {
            $this->_compareList = Mage::getModel('catalog/product_compare_list');
        }
        else {
            $this->_compareList = false;
        }
        return $this->_compareList;
    }

    public function getCustomerGroupId()
    {
        $groupId = $this->getQuote()->getCustomerGroupId();
        if (!$groupId) {
            $groupId = $this->getSession()->getCustomerGroupId();
        }
        return $groupId;
    }

    /**
     * Move quote item to another items store
     *
     * @param   mixed $item
     * @param   string $mogeTo
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function moveQuoteItem($item, $moveTo, $qty)
    {
        if ($item = $this->_getQuoteItem($item)) {
            switch ($moveTo) {
                case 'cart':
                    if ($cart = $this->getCustomerCart()) {
                        $cartItem = $cart->addCatalogProduct($item->getProduct());
                        $cartItem->setQty($qty);
                        $cartItem->setPrice($item->getProduct()->getPrice());
                        $cart->collectTotals()
                            ->save();
                    }
                    break;
                case 'wishlist':
                    if ($wishlist = $this->getCustomerWishlist()) {
                        $wishlist->addNewItem($item->getProduct()->getId());
                    }
                    break;
                case 'comparelist':

                    break;
                default:
                    break;
            }
            $this->getQuote()->removeItem($item->getId());
            $this->setRecollect(true);
        }
        return $this;
    }

    public function applySidebarData($data)
    {
        if (isset($data['add'])) {
            foreach ($data['add'] as $productId=>$qty) {
                $this->addProduct($productId, $qty);
            }
        }
        if (isset($data['remove'])) {
            foreach ($data['remove'] as $itemId => $from) {
                $this->removeItem($itemId, $from);
            }
        }
        return $this;
    }

    /**
     * Remove item from some of customer items storage (shopping cart, wishlist etc.)
     *
     * @param   int $itemId
     * @param   string $from
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function removeItem($itemId, $from)
    {
        switch ($from) {
            case 'quote':
                $this->removeQuoteItem($itemId);
                break;
            case 'cart':
                if ($cart = $this->getCustomerCart()) {
                    $cart->removeItem($itemId);
                    $cart->collectTotals()
                        ->save();
                }
                break;
            case 'wishlist':
                if ($wishlist = $this->getCustomerWishlist()) {
                    $item = Mage::getModel('wishlist/item')->load($itemId);
                    $item->delete();
                }
                break;
            case 'compared':
                $item = Mage::getModel('catalog/product_compare_item')
                    ->load($itemId)
                    ->delete();
                break;
        }
        return $this;
    }

    /**
     * Remove quote item
     *
     * @param   int $item
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function removeQuoteItem($item)
    {
        $this->getQuote()->removeItem($item);
        $this->setRecollect(true);
        return $this;
    }

    /**
     * Add product to current order quote
     *
     * @param   mixed $product
     * @param   mixed $qty
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function addProduct($product, $qty=1)
    {
        $qty = (int) $qty;
        if (!($product instanceof Mage_Catalog_Model_Product)) {
            $product = Mage::getModel('catalog/product')
                ->setStore($this->getSession()->getStore())
                ->load($product);
        }

        if ($item = $this->getQuote()->getItemByProduct($product)) {
            $item->setQty($item->getQty()+$qty);
        }
        else {
            $item = $this->getQuote()->addCatalogProduct($product);
            $item->setQty($qty);
        }

        $this->setRecollect(true);
        return $this;
    }

    /**
     * Add multiple products to current order quote
     *
     * @param   array $products
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function addProducts(array $products)
    {
        foreach ($products as $productId => $data) {
            $qty = isset($data['qty']) ? (int)$data['qty'] : 1;
            try {
                $this->addProduct($productId, $qty);
            }
            catch (Mage_Core_Exception $e){
                $this->getSession()->addError($e->getMessage());
            }
            catch (Exception $e){
                return $e;
            }
        }
        return $this;
    }

    /**
     * Update quantity of order quote items
     *
     * @param   array $data
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function updateQuoteItems($data)
    {
        if (is_array($data)) {
            foreach ($data as $itemId => $info) {
                $itemQty    = (int) $info['qty'];
                $itemQty    = $itemQty>0 ? $itemQty : 1;
                if (isset($info['custom_price'])) {
                    $itemPrice  = $this->_parseCustomPrice($info['custom_price']);;
                }
                else {
                    $itemPrice = null;
                }
                $noDiscount = !isset($info['use_discount']);

                if (empty($info['action'])) {

                    if ($item = $this->getQuote()->getItemById($itemId)) {
                       $item->setQty($itemQty);
                       $item->setCustomPrice($itemPrice);
                       $item->setNoDiscount($noDiscount);
                    }
                }
                else {
                    $this->moveQuoteItem($itemId, $info['action'], $itemQty);
                }
            }
            $this->setRecollect(true);
        }
        return $this;
    }

    protected function _parseCustomPrice($price)
    {
        $price = floatval($price);
        $price = $price>0 ? $price : 0;
        return $price;
    }

    /**
     * Retrieve oreder quote shipping address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getShippingAddress()
    {
        return $this->getQuote()->getShippingAddress();
    }

    public function setShippingAddress($address)
    {
        if (is_array($address)) {
            $shippingAddress = Mage::getModel('sales/quote_address')
                ->setData($address);
            $shippingAddress->implodeStreetAddress();
        }
        if ($address instanceof Mage_Sales_Model_Quote_Address) {
            $shippingAddress = $address;
        }

        $this->setRecollect(true);
        $this->getQuote()->setShippingAddress($shippingAddress);
        return $this;
    }

    public function setShippingAsBilling($flag)
    {
        if ($flag) {
            $tmpAddress = clone $this->getBillingAddress();
            $tmpAddress->unsEntityId()
                ->unsAddressType();
            $this->getShippingAddress()->addData($tmpAddress->getData());
        }
        $this->getShippingAddress()->setSameAsBilling($flag);
        $this->setRecollect(true);
        return $this;
    }

    /**
     * Retrieve quote billing address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getBillingAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

    public function setBillingAddress($address)
    {
        if (is_array($address)) {
            $billingAddress = Mage::getModel('sales/quote_address')
                ->setData($address);
            $billingAddress->implodeStreetAddress();
        }

        if ($this->getShippingAddress()->getSameAsBilling()) {
            $shippingAddress = clone $billingAddress;
            $shippingAddress->setSameAsBilling(true);
            $this->setShippingAddress($address);
        }

        $this->getQuote()->setBillingAddress($billingAddress);
        return $this;
    }

    public function setShippingMethod($method)
    {
        $this->getShippingAddress()->setShippingMethod($method);
        $this->setRecollect(true);
        return $this;
    }

    public function resetShippingMethod()
    {
        $this->getShippingAddress()->setShippingMethod(false);
        $this->getShippingAddress()->removeAllShippingRates();
        return $this;
    }

    public function collectShippingRates()
    {
        $this->getQuote()->collectTotals();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->getShippingAddress()->collectShippingRates();
        return $this;
    }

    public function setPaymentMethod($method)
    {
        $this->getQuote()->getPayment()->setMethod($method);
        return $this;
    }

    public function setPaymentData($data)
    {
        if (!isset($data['method'])) {
            $data['method'] = $this->getQuote()->getPayment()->getMethod();
        }
        $this->getQuote()->getPayment()->importData($data);
        return $this;
    }

    public function applyCoupon($code)
    {
        $code = trim((string)$code);
        $this->getQuote()->setCouponCode($code);
        $this->setRecollect(true);
        return $this;
    }

    public function setAccountData($accountData)
    {
        $data = array();
        foreach ($accountData as $key => $value) {
            $data['customer_'.$key] = $value;
        }

        if (isset($data['customer_group_id'])) {
            $groupModel = Mage::getModel('customer/group')->load($data['customer_group_id']);
            $data['customer_tax_class_id'] = $groupModel->getTaxClassId();
            $this->setRecollect(true);
        }

        $this->getQuote()->addData($data);
        return $this;
    }

    /**
     * Parse data retrieved from request
     *
     * @param   array $data
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function importPostData($data)
    {
        $this->addData($data);

        if (isset($data['account'])) {
            $this->setAccountData($data['account']);
        }

        if (isset($data['comment'])) {
            $this->getQuote()->addData($data['comment']);
        }

        if (isset($data['billing_address'])) {
            $data['billing_address']['customer_address_id'] =
                isset($data['customer_address_id']) ? $data['customer_address_id'] : '';
            $this->setBillingAddress($data['billing_address']);
        }

        if (isset($data['shipping_address'])) {
            $data['shipping_address']['customer_address_id'] =
                isset($data['customer_address_id']) ? $data['customer_address_id'] : '';
            $this->setShippingAddress($data['shipping_address']);
        }

        if (isset($data['shipping_method'])) {
            $this->setShippingMethod($data['shipping_method']);
        }

        if (isset($data['payment_method'])) {
            $this->setPaymentMethod($data['payment_method']);
        }

        if (isset($data['coupon']['code'])) {
            $this->applyCoupon($data['coupon']['code']);
        }

        return $this;
    }

    /**
     * Create new order
     *
     * @return Mage_Sales_Model_Order
     */
    public function createOrder()
    {
        $this->_validate();
        if (!$this->getQuote()->getCustomerIsGuest()) {
            $this->_saveCustomer();
        }

        $quoteConvert = Mage::getModel('sales/convert_quote');

        /* @var $quoteConvert Mage_Sales_Model_Convert_Quote */

        $quote = $this->getQuote();

        $order = $quoteConvert->addressToOrder($quote->getShippingAddress());
        $order->setBillingAddress($quoteConvert->addressToOrderAddress($quote->getBillingAddress()))
            ->setShippingAddress($quoteConvert->addressToOrderAddress($quote->getShippingAddress()))
            ->setPayment($quoteConvert->paymentToOrderPayment($quote->getPayment()));

        foreach ($quote->getShippingAddress()->getAllItems() as $item) {
            $order->addItem($quoteConvert->itemToOrderItem($item));
        }

        if ($this->getSendConfirmation()) {
            $order->setEmailSent(true);
        }

        $order->place()
            ->save();

        if ($this->getSession()->getOrder()->getId()) {
            $oldOrder = $this->getSession()->getOrder();
            $originalId = $oldOrder->getOriginalIncrementId() ? $oldOrder->getOriginalIncrementId() : $oldOrder->getIncrementId();
            $order->setOriginalIncrementId($originalId);
            $order->setRelationParentId($oldOrder->getId());
            $order->setRelationParentRealId($oldOrder->getIncrementId());
            $order->setEditIncrement($oldOrder->getEditIncrement()+1);
            $order->setIncrementId($originalId.'-'.$order->getEditIncrement());

            $this->getSession()->getOrder()->setRelationChildId($order->getId());
            $this->getSession()->getOrder()->setRelationChildRealId($order->getIncrementId());
            $this->getSession()->getOrder()->cancel()
                ->save();
            $order->save();
        }

        if ($this->getSendConfirmation()) {
            $order->sendNewOrderEmail();
        }

        return $order;
    }

    /**
     * Validate quote data before order creation
     *
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _validate()
    {
        $customerId = $this->getSession()->getCustomerId();
        if (is_null($customerId)) {
            Mage::throwException(Mage::helper('adminhtml')->__('Please select a custmer'));
        }

        if (!$this->getSession()->getStore()->getId()) {
            Mage::throwException(Mage::helper('adminhtml')->__('Please select a store'));
        }
        $items = $this->getQuote()->getAllItems();

        $errors = array();
        if (count($items) == 0) {
            $errors[] = Mage::helper('adminhtml')->__('You need specify order items');
        }

        if (!$this->getQuote()->getShippingAddress()->getShippingMethod()) {
            $errors[] = Mage::helper('adminhtml')->__('Shipping method must be specified');
        }

        if (!$this->getQuote()->getPayment()->getMethod()) {
            $errors[] = Mage::helper('adminhtml')->__('Payment method must be specified');
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->getSession()->addError($error);
            }
            Mage::throwException('');
        }
        return $this;
    }

    /**
     * Save order customer account data
     *
     * @return unknown
     */
    protected function _saveCustomer()
    {
        if (!$this->getSession()->getCustomer()->getId()) {
            $customer = Mage::getModel('customer/customer');
            /* @var $customer Mage_Customer_Model_Customer*/

            $billingAddress = $this->getBillingAddress()->exportCustomerAddress();

            $customer->addData($billingAddress->getData())
                ->addData($this->getData('account'))
                ->setPassword($customer->generatePassword())
                ->setWebsiteId($this->getSession()->getStore()->getWebsiteId())
                ->setStoreId($this->getSession()->getStore()->getId())
                ->addAddress($billingAddress);

            if (!$this->getShippingAddress()->getSameAsBilling()) {
                $shippingAddress = $this->getShippingAddress()->exportCustomerAddress();
                $customer->addAddress($shippingAddress);
            }
            else {
                $shippingAddress = $billingAddress;
            }
            $customer->save();


            $customer->setEmail($this->_getNewCustomerEmail($customer))
                ->setDefaultBilling($billingAddress->getId())
                ->setDefaultShipping($shippingAddress->getId())
                ->save();

            $this->getBillingAddress()->setCustomerId($customer->getId());
            $this->getShippingAddress()->setCustomerId($customer->getId());

            $customer->sendNewAccountEmail();
        }
        else {
            $customer = $this->getSession()->getCustomer();
            $customer->addData($this->getData('account'));
            /**
             * don't save account information, use it only for order creation
             */
            //$customer->save();
        }
        $this->getQuote()->setCustomer($customer);
        return $this;
    }

    /**
     * Retrieve new customer email
     *
     * @param   Mage_Customer_Model_Customer $customer
     * @return  string
     */
    protected function _getNewCustomerEmail($customer)
    {
        $email = $this->getData('account/email');
        if (empty($email)) {
            $host = $this->getSession()->getStore()->getConfig(Mage_Customer_Model_Customer::XML_PATH_DEFAULT_EMAIL_DOMAIN);
            $email = $customer->getIncrementId().'@'. $host;
        }
        return $email;
    }
}