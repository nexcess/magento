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
 * Quote model
 *
 * Supported events:
 *  sales_quote_load_after
 *  sales_quote_save_before
 *  sales_quote_save_after
 *  sales_quote_delete_before
 *  sales_quote_delete_after
 *
 */
class Mage_Sales_Model_Quote extends Mage_Core_Model_Abstract
{
    const CACHE_TAG         = 'sales_quote';
    protected $_cacheTag    = 'sales_quote';

    protected $_eventPrefix = 'sales_quote';
    protected $_eventObject = 'quote';

    /**
     * Quote customer model object
     *
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer;

    /**
     * Quote addresses collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_addresses;

    /**
     * Quote items collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_items;

    /**
     * Quote payments
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_payments;

    /**
     * Cache key prefix to be used in quote items
     * If null - cache is disabled
     *
     * @var string
     */
    protected $_cacheKey;

    protected $_cacheTags = array();

    /**
     * Init resource model
     */
    protected function _construct()
    {#mageDebugBacktrace();
        $this->_init('sales/quote');
    }

    public function setCacheKey($key)
    {
        $this->_cacheKey = $key;
        return $this;
    }

    public function getCacheKey($quoteId)
    {
        /**
         * Quote without cache
         */
        return false;
        if (!Mage::app()->useCache('checkout_quote')) {
            return false;
        }
        if ($this->_cacheKey===true) {
            $this->_cacheKey = 'CHECKOUT_QUOTE'.$quoteId.'_STORE'.$this->getStoreId();
        }
        return $this->_cacheKey;
    }

    public function setCacheTags($tags, $reset=false)
    {
        $this->_cacheTags = $tags;
        return $this;
    }

    public function getCacheTags()
    {
        $tags = (array)$this->_cacheTags;
        $tags[] = $this->_cacheKey;
        $tags[] = 'checkout_quote';

        if ($this->getId()) {
            $tags[] = 'checkout_quote_'.$this->getId();
        }

        if (!empty($this->_items) && $this->_items->isLoaded()) {
            foreach ($this->getItemsCollection() as $item) {
                $tags[] = 'catalog_product_'.$item->getProductId();
            }
        }

        if ($this->getCouponCode()) {
            $tags[] = 'salesrule_coupon_'.md5($this->getCouponCode());
        }

        return array_unique($tags);
    }

    public function getCacheLifetime()
    {
        return 86400;
    }

    public function getStoreId()
    {
        if (!$this->hasStoreId()) {
            return Mage::app()->getStore()->getId();
        }
        return $this->getData('store_id');
    }

    /**
     * Retrieve quote store model object
     *
     * @return  Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore($this->getStoreId());
    }

    /**
     * Declare quote store model
     *
     * @param   Mage_Core_Model_Store $store
     * @return  Mage_Sales_Model_Quote
     */
    public function setStore(Mage_Core_Model_Store $store)
    {
        $this->setStoreId($store->getId());
        return $this;
    }

    public function getSharedStoreIds()
    {
        $ids = $this->getData('shared_store_ids');
        if (is_null($ids) || !is_array($ids)) {
            return $this->getStore()->getWebsite()->getStoreIds();
        }
        return $ids;
    }

    public function load($id, $field=null)
    {
        if (!$key = $this->getCacheKey($id)) {
            Varien_Profiler::start('TEST1: '.__METHOD__);
            parent::load($id, $field);
            Varien_Profiler::stop('TEST1: '.__METHOD__);
        } elseif ($cache = Mage::app()->loadCache($key)) {
            Varien_Profiler::start('TEST2: '.__METHOD__);
            $this->fromArray(unserialize($cache));
            $this->_afterLoad();
            $this->setOrigData();
            Varien_Profiler::stop('TEST2: '.__METHOD__);
        } else {
            Varien_Profiler::start('TEST3: '.__METHOD__);
            parent::load($id, $field);
            $this->saveCache();
            Varien_Profiler::stop('TEST3: '.__METHOD__);
        }
        return $this;
    }

    public function saveCache()
    {
        $key = $this->getCacheKey($this->getId());
        if ($key) {
            $data = $this->toArray();
            Mage::app()->saveCache(serialize($data), $key,
                $this->getCacheTags(), $this->getCacheLifetime());
        }
        return $this;
    }

    public function cleanCache()
    {
        Mage::app()->cleanCache(array('checkout_quote_'.$this->getId()));
        return $this;
    }

    public function fromArray(array $data)
    {
        $this->setData($data);
        return $this;
    }

    /**
     * Prepare data before save
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _beforeSave()
    {
        $baseCurrencyCode  = Mage::app()->getBaseCurrencyCode();
        $storeCurrency = $this->getStore()->getBaseCurrency();
        $quoteCurrency = $this->getStore()->getCurrentCurrency();

        $this->setBaseCurrencyCode($baseCurrencyCode);
        $this->setStoreCurrencyCode($storeCurrency->getCode());
        $this->setQuoteCurrencyCode($quoteCurrency->getCode());
        $this->setStoreToBaseRate($storeCurrency->getRate($baseCurrencyCode));
        $this->setStoreToQuoteRate($storeCurrency->getRate($quoteCurrency));

        parent::_beforeSave();
    }

    protected function _afterSave()
    {
        parent::_afterSave();
        $this->getAddressesCollection()->save();
        $this->getItemsCollection()->save();
        $this->getPaymentsCollection()->save();
        $this->cleanCache();
        return $this;
    }

    /**
     * Loading quote data by customer
     *
     * @return mixed
     */
    public function loadByCustomer($customer)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customerId = $customer->getId();
            $this->setStoreId($customer->getStoreId());
        }
        else {
            $customerId = (int) $customer;
        }
        $this->_getResource()->loadByCustomerId($this, $customerId);
        $this->_afterLoad();
        return $this;
    }

    /**
     * Assign customer model object data to quote
     *
     * @param   Mage_Customer_Model_Customer $customer
     * @return  Mage_Sales_Model_Quote
     */
    public function assignCustomer(Mage_Customer_Model_Customer $customer)
    {
        if ($customer->getId()) {
            $this->setCustomer($customer);

            $defaultBillingAddress = $customer->getDefaultBillingAddress();
            if ($defaultBillingAddress && $defaultBillingAddress->getId()) {
                $billingAddress = Mage::getModel('sales/quote_address')
                ->importCustomerAddress($defaultBillingAddress);
                $this->setBillingAddress($billingAddress);
            }

            $defaultShippingAddress= $customer->getDefaultShippingAddress();
            if ($defaultShippingAddress && $defaultShippingAddress->getId()) {
                $shippingAddress = Mage::getModel('sales/quote_address')
                ->importCustomerAddress($defaultShippingAddress);
            }
            else {
                $shippingAddress = Mage::getModel('sales/quote_address');
            }
            $this->setShippingAddress($shippingAddress);
        }

        return $this;
    }

    /**
     * Define customer object
     *
     * @param   Mage_Customer_Model_Customer $customer
     * @return  Mage_Sales_Model_Quote
     */
    public function setCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_customer = $customer;
        $this->setCustomerId($customer->getId());
        $this->setCustomerEmail($customer->getEmail());
        $this->setCustomerFirstname($customer->getFirstname());
        $this->setCustomerLastname($customer->getLastname());
        $this->setCustomerGroupId($customer->getGroupId());
        $this->setCustomerTaxClassId($customer->getTaxClassId());
        return $this;
    }

    /**
     * Retrieve customer model object
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        if (is_null($this->_customer)) {
            $this->_customer = Mage::getModel('customer/customer');
            if ($customerId = $this->getCustomerId()) {
                $this->_customer->load($customerId);
                if (!$this->_customer->getId()) {
                    $this->_customer->setCustomerId(null);
                }
            }
        }
        return $this->_customer;
    }

    public function getCustomerTaxClassId()
    {
        if (!$this->getData('customer_group_id') && !$this->getData('customer_tax_class_id')) {
            $classId = Mage::getModel('customer/group')->getTaxClassId($this->getCustomerGroupId());
            $this->setCustomerTaxClassId($classId);
        }
        return $this->getData('customer_tax_class_id');
    }

    /**
     * Retrieve quote address collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getAddressesCollection()
    {
        if (is_null($this->_addresses)) {
            $this->_addresses = Mage::getModel('sales/quote_address')->getCollection()
                ->addAttributeToSelect('*')
                ->setQuoteFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_addresses as $address) {
                    $address->setQuote($this);
                }
            }
        }
        return $this->_addresses;
    }

    /**
     * Retrieve quote address by type
     *
     * @param   string $type
     * @return  Mage_Sales_Model_Quote_Address
     */
    protected function _getAddressByType($type)
    {
        foreach ($this->getAddressesCollection() as $address) {
            if ($address->getAddressType() == $type && !$address->isDeleted()) {
                return $address;
            }
        }

        $address = Mage::getModel('sales/quote_address')->setAddressType($type);
        $this->addAddress($address);
        return $address;
    }

    /**
     * Retrieve quote billing address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getBillingAddress()
    {
        return $this->_getAddressByType('billing');
    }

    /**
     * retrieve quote shipping address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getShippingAddress()
    {
        return $this->_getAddressByType('shipping');
    }

    public function getAllShippingAddresses()
    {
        $addresses = array();
        foreach ($this->getAddressesCollection() as $address) {
            if ($address->getAddressType()=='shipping' && !$address->isDeleted()) {
                $addresses[] = $address;
            }
        }
        return $addresses;
    }

    public function getAllAddresses()
    {
        $addresses = array();
        foreach ($this->getAddressesCollection() as $address) {
            if (!$address->isDeleted()) {
                $addresses[] = $address;
            }
        }
        return $addresses;
    }

    public function getAddressById($addressId)
    {
        foreach ($this->getAddressesCollection() as $address) {
            if ($address->getId()==$addressId) {
                return $address;
            }
        }
        return false;
    }

    public function getAddressByCustomerAddressId($addressId)
    {
        foreach ($this->getAddressesCollection() as $address) {
            if (!$address->isDeleted() && $address->getCustomerAddressId()==$addressId) {
                return $address;
            }
        }
        return false;
    }

    public function getShippingAddressByCustomerAddressId($addressId)
    {
        foreach ($this->getAddressesCollection() as $address) {
            if (!$address->isDeleted() && $address->getAddressType()=='shipping' && $address->getCustomerAddressId()==$addressId) {
                return $address;
            }
        }
        return false;
    }

    public function removeAddress($addressId)
    {
        foreach ($this->getAddressesCollection() as $address) {
            if ($address->getId()==$addressId) {
                $address->isDeleted(true);
                break;
            }
        }
        return $this;
    }

    public function removeAllAddresses()
    {
        foreach ($this->getAddressesCollection() as $address) {
            $address->isDeleted(true);
        }
        return $this;
    }

    public function addAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $address->setQuote($this)->setParentId($this->getId());
        if (!$address->getId()) {
            $this->getAddressesCollection()->addItem($address);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Mage_Sales_Model_Quote
     */
    public function setBillingAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $old = $this->getBillingAddress();

        if (!empty($old)) {
            $old->addData($address->getData());
        } else {
            $this->addAddress($address->setAddressType('billing'));
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Mage_Sales_Model_Quote
     */
    public function setShippingAddress(Mage_Sales_Model_Quote_Address $address)
    {
        if ($this->getIsMultiShipping()) {
            $this->addAddress($address->setAddressType('shipping'));
        }
        else {
            $old = $this->getShippingAddress();

            if (!empty($old)) {
                $old->addData($address->getData());
            } else {
                $this->addAddress($address->setAddressType('shipping'));
            }
        }
        return $this;
    }

    public function addShippingAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $this->setShippingAddress($address);
        return $this;
    }

    /*********************** ITEMS ***************************/
    /**
     * Retrieve quote items collection
     *
     * @param   bool $loaded
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getItemsCollection($useCache = true)
    {
        if (is_null($this->_items)) {
Varien_Profiler::start('TEST1/1: '.__METHOD__);
            $this->_items = Mage::getResourceModel('sales/quote_item_collection');
Varien_Profiler::stop('TEST1/1: '.__METHOD__);
Varien_Profiler::start('TEST1/2: '.__METHOD__);
            $this->_items->addAttributeToSelect('*');
Varien_Profiler::stop('TEST1/2: '.__METHOD__);
            $this->_items->setQuote($this);

        if ($useCache) {
                if ($key = $this->getCacheKey($this->getId())) {
                $this->_items->initCache(Mage::app()->getCache(), $key.'_ITEMS', $this->getCacheTags());
                }

            if ($this->getId()) {
Varien_Profiler::start('TEST3: '.__METHOD__);
                    $items = $this->_items->getIterator();
Varien_Profiler::stop('TEST3: '.__METHOD__);
                foreach ($items as $item) {
                        $item->setQuote($this);
                    }
            }
        }
        }
        return $this->_items;
    }

    /**
     * Retrieve quote items array
     *
     * @return array
     */
    public function getAllItems()
    {
        $items = array();
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted()) {
                $items[] =  $item;
            }
        }
        return $items;
    }

    /**
     * Checking items availability
     *
     * @return bool
     */
    public function hasItems()
    {
        return sizeof($this->getAllItems())>0;
    }

    public function isAllowedGuestCheckout()
    {
        return Mage::getStoreConfig('checkout/options/guest_checkout');
    }

    /**
     * Checking availability of items with decimal qty
     *
     * @return bool
     */
    public function hasItemsWithDecimalQty()
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->getProduct()->getStockItem()
                && $item->getProduct()->getStockItem()->getIsQtyDecimal()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve item model object by item identifier
     *
     * @param   int $itemId
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function getItemById($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getId()==$itemId) {
                return $item;
            }
        }
        return false;
    }

    /**
     * Remove quote item by item identifier
     *
     * @param   int $itemId
     * @return  Mage_Sales_Model_Quote
     */
    public function removeItem($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getId()==$itemId) {
                $item->isDeleted(true);
                break;
            }
        }
        return $this;
    }

    /**
     * Adding new item to quote
     *
     * @param   Mage_Sales_Model_Quote_Item $item
     * @return  Mage_Sales_Model_Quote
     */
    public function addItem(Mage_Sales_Model_Quote_Item $item)
    {
        $item->setQuote($this)
            ->setParentId($this->getId());
        if (!$item->getId()) {
            $this->getItemsCollection()->addItem($item);
        }
        return $this;
    }

    /**
     * Adding product to quote
     *
     * @param   mixed $product
     * @return  Mage_Sales_Model_Quote
     */
    public function addProduct($product, $qty=1)
    {
        if (is_int($product)) {
            $product = Mage::getModel('catalog/product')
                ->setStore($this->getStore())
                ->load($product);
        }

        if ($product instanceof Mage_Catalog_Model_Product) {
            $this->addCatalogProduct($product, $qty);
        }

        return $this;
    }

    /**
     * Adding catalog product object data to quote
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function addCatalogProduct(Mage_Catalog_Model_Product $product, $qty=1)
    {
        $item = $this->getItemByProduct($product);
        if (!$item) {
            $item = Mage::getModel('sales/quote_item');
        }
        /* @var $item Mage_Sales_Model_Quote_Item */

        $item->importCatalogProduct($product)
            ->addQty($qty);

        $this->addItem($item);

        return $item;
    }

    /**
     * Retrieve quote item by product id
     *
     * @param   int $productId
     * @return  Mage_Sales_Model_Quote_Item || false
     */
    public function getItemByProduct($product, $superProductId = null)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId      = $product->getId();
            $superProductId = $product->getSuperProduct() ? $product->getSuperProduct()->getId() : null;
        }
        else {
            $productId = $product;
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getSuperProductId()) {
                if ($superProductId && $item->getSuperProductId() == $superProductId) {
                    if ($item->getProductId() == $productId) {
                        return $item;
                    }
                }
            }
            else {
                if ($item->getProductId() == $productId) {
                    return $item;
                }
            }
        }
        return false;
    }

    public function getItemsSummaryQty()
    {
        $qty = $this->getData('all_items_qty');
        if (is_null($qty)) {
            #            $qty = Mage::getResourceModel('sales_entity/quote')->fetchItemsSummaryQty($this);
            $qty = 0;
            foreach ($this->getAllItems() as $item) {
                $qty+= $item->getQty();
            }
            $this->setData('all_items_qty', $qty);
        }
        return $qty;
    }

    /*********************** PAYMENTS ***************************/
    public function getPaymentsCollection()
    {
        if (is_null($this->_payments)) {
            $this->_payments = Mage::getResourceModel('sales/quote_payment_collection')
            ->addAttributeToSelect('*')
            ->setQuoteFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_payments as $payment) {
                    $payment->setQuote($this);
                }
            }
        }
        return $this->_payments;
    }

    /**
     * @return Mage_Sales_Model_Quote_Payment
     */
    public function getPayment()
    {
        foreach ($this->getPaymentsCollection() as $payment) {
            if (!$payment->isDeleted()) {
                return $payment;
            }
        }
        $payment = Mage::getModel('sales/quote_payment');
        $this->addPayment($payment);
        return $payment;
    }

    public function getPaymentById($paymentId)
    {
        foreach ($this->getPaymentsCollection() as $payment) {
            if ($payment->getId()==$paymentId) {
                return $payment;
            }
        }
        return false;
    }

    public function addPayment(Mage_Sales_Model_Quote_Payment $payment)
    {
        $payment->setQuote($this)->setParentId($this->getId());
        if (!$payment->getId()) {
            $this->getPaymentsCollection()->addItem($payment);
        }
        return $this;
    }

    public function setPayment(Mage_Sales_Model_Quote_Payment $payment)
    {
        if (!$this->getIsMultiPayment() && ($old = $this->getPayment())) {
            $payment->setId($old->getId());
        }
        $this->addPayment($payment);

        return $payment;
    }

    public function removePayment()
    {
        $this->getPayment()->isDeleted(true);
        return $this;
    }

    /*********************** TOTALS ***************************/
    public function collectTotals()
    {
        $this->setGrandTotal(0);
        $this->setBaseGrandTotal(0);
        foreach ($this->getAllShippingAddresses() as $address) {
            $address->setGrandTotal(0);
            $address->setBaseGrandTotal(0);

            $address->collectTotals();

            $this->setGrandTotal((float) $this->getGrandTotal()+$address->getGrandTotal());
            $this->setBaseGrandTotal((float) $this->getBaseGrandTotal()+$address->getBaseGrandTotal());
        }
        Mage::helper('sales')->checkQuoteAmount($this, $this->getGrandTotal());
        Mage::helper('sales')->checkQuoteAmount($this, $this->getBaseGrandTotal());

        $this->setItemsCount(0);
        $this->setItemsQty(0);

        foreach ($this->getAllItems() as $item) {
            $this->setItemsCount($this->getItemsCount()+1);
            $this->setItemsQty((float) $this->getItemsQty()+$item->getQty());
        }
        return $this;
    }

    public function getTotals()
    {
        return $this->getShippingAddress()->getTotals();
    }

    /*********************** ORDER ***************************/
    public function createOrder()
    {
        if ($this->getIsVirtual()) {
            $this->getBillingAddress()->createOrder();
        } elseif (!$this->getIsMultiShipping()) {
            $this->getShippingAddress()->createOrder();
        } else {
            foreach ($this->getAllShippingAddresses() as $address) {
                $address->createOrder();
            }
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Quote
     */
    public function createFromOrder(Mage_Sales_Model_Order $order)
    {
        $this->setStoreId($order->getStoreId());
        $this->setBillingAddress(Mage::getModel('sales/quote_address')->importOrderAddress($order->getBillingAddress()));
        $this->setShippingAddress(Mage::getModel('sales/quote_address')->importOrderAddress($order->getShippingAddress()));
        foreach ($order->getItemsCollection() as $item) {
            if ($item->getQtyToShip() > 0) {
                $this->addItem(Mage::getModel('sales/quote_item')->importOrderItem($item));
            }
        }
        $this->getShippingAddress()->setCollectShippingRates(true);
        $this->getShippingAddress()->setShippingMethod($order->getShippingMethod());
        $this->getPayment()->importOrderPayment($order->getPayment());
        $this->setCouponCode($order->getCouponeCode());
        return $this;
    }

    public function addMessage($message, $index='error')
    {
        $messages = $this->getData('messages');
        if (is_null($messages)) {
            $messages = array();
        }

        if (isset($messages[$index])) {
            return $this;
        }

        if (is_string($message)) {
            $message = Mage::getSingleton('core/message')->error($message);
        }

        $messages[$index] = $message;
        $this->setData('messages', $messages);
        return $this;
    }

    public function getMessages()
    {
        $messages = $this->getData('messages');
        if (is_null($messages)) {
            $messages = array();
            $this->setData('messages', $messages);
        }
        return $messages;
    }

    /*********************** QUOTE ***************************/
    protected function _beforeDelete()
    {
        parent::_beforeDelete();

        $this->getAddressesCollection()->walk('delete');
        $this->getItemsCollection()->walk('delete');
        $this->getPaymentsCollection()->walk('delete');
    }
}