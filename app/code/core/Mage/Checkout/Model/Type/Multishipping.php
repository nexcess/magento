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
 * Multishipping checkout model
 *
 * @category   Mage
 * @package    Mage_Checkout
 */
class Mage_Checkout_Model_Type_Multishipping extends Mage_Checkout_Model_Type_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->_init();
    }

    /**
     * Initialize multishipping checkout
     *
     * @return Mage_Checkout_Model_Type_Multishipping
     */
    protected function _init()
    {
        /**
         * reset quote shipping addresses and items
         */
        $this->getQuote()->setIsMultiShipping(true);
        if ($this->getCheckoutSession()->getCheckoutState() === Mage_Checkout_Model_Session::CHECKOUT_STATE_BEGIN) {
            $this->getCheckoutSession()->setCheckoutState(true);

            $addresses  = $this->getQuote()->getAllShippingAddresses();
            foreach ($addresses as $address) {
                $this->getQuote()->removeAddress($address->getId());
            }

            if ($defaultShipping = $this->getCustomerDefaultShippingAddress()) {
                $quoteShippingAddress = $this->getQuote()->getShippingAddress();
                $quoteShippingAddress->importCustomerAddress($defaultShipping);

                foreach ($this->getQuoteItems() as $item) {
                    $addressItem = Mage::getModel('sales/quote_address_item')
                        ->importQuoteItem($item);

                    $quoteShippingAddress->addItem($addressItem);
                }
                /**
                 * Collect rates before display shipping methods
                 */
                //$quoteShippingAddress->setCollectShippingRates(true);
            }

            if ($this->getCustomerDefaultBillingAddress()) {
                $this->getQuote()->getBillingAddress()
                    ->importCustomerAddress($this->getCustomerDefaultBillingAddress());
            }

            $this->save();
        }
        $this->getQuote()->collectTotals();
        return $this;
    }

    public function getQuoteShippingAddressesItems()
    {
        $items = array();
        $addresses  = $this->getQuote()->getAllShippingAddresses();
        foreach ($addresses as $address) {
            foreach ($address->getAllItems() as $item) {
                for ($i=0;$i<$item->getQty();$i++){
                    $addressItem = clone $item;
                    $addressItem->setQty(1)
                        ->setCustomerAddressId($address->getCustomerAddressId());
                    $items[] = $addressItem;
                }
            }
        }
        return $items;
    }

    public function removeAddressItem($addressId, $itemId)
    {
        $address = $this->getQuote()->getAddressById($addressId);
        if ($address) {
            if ($item = $address->getItemById($itemId)) {
                if ($item->getQty()>1) {
                    $item->setQty($item->getQty()-1);
                }
                else {
                    $address->removeItem($item->getId());
                }

                if (count($address->getAllItems()) == 0) {
                    $address->isDeleted(true);
                }

                if ($quoteItem = $this->getQuote()->getItemById($item->getQuoteItemId())) {
                    $newItemQty = $quoteItem->getQty()-1;
                    if ($newItemQty>0) {
                        $quoteItem->setQty($quoteItem->getQty()-1);
                    }
                    else {
                        $this->getQuote()->removeItem($quoteItem->getId());
                    }
                }

                $this->save();
            }
        }
        return $this;
    }

    public function setShippingItemsInformation($info)
    {
        if (is_array($info)) {
            $allQty = 0;
            foreach ($info as $itemData) {
                foreach ($itemData as $quoteItemId => $data) {
                    $allQty += $data['qty'];
                }
            }

            $maxQty = (int)Mage::getStoreConfig('shipping/option/checkout_multiple_maximum_qty');
            if ($allQty > $maxQty) {
                Mage::throwException(Mage::helper('checkout')->__('Maximum qty allowed for Shipping to multiple addresses is %s', $maxQty));
            }

            $addresses  = $this->getQuote()->getAllShippingAddresses();
            foreach ($addresses as $address) {
                $this->getQuote()->removeAddress($address->getId());
            }

            foreach ($info as $itemData) {
                foreach ($itemData as $quoteItemId => $data) {
                    $this->_addShippingItem($quoteItemId, $data);
                }
            }
            $this->save();
            Mage::dispatchEvent('checkout_type_multishipping_set_shipping_items', array('quote'=>$this->getQuote()));
        }
        return $this;
    }

    protected function _addShippingItem($quoteItemId, $data)
    {
        $qty       = isset($data['qty']) ? (int) $data['qty'] : 0;
        $qty       = $qty > 0 ? $qty : 1;
        $addressId = isset($data['address']) ? (int) $data['address'] : false;
        $quoteItem = $this->getQuote()->getItemById($quoteItemId);

        if ($addressId && $quoteItem) {
            $quoteItem->setMultisippingQty((int)$quoteItem->getMultisippingQty()+$qty);
            $quoteItem->setQty($quoteItem->getMultisippingQty());

            $address = $this->getCustomer()->getAddressById($addressId);
            if ($address) {
                if (!$quoteAddress = $this->getQuote()->getShippingAddressByCustomerAddressId($addressId)) {
                    $quoteAddress = Mage::getModel('sales/quote_address')
                       ->importCustomerAddress($address);
                    $this->getQuote()->addShippingAddress($quoteAddress);
                }

                $quoteAddress = $this->getQuote()->getShippingAddressByCustomerAddressId($address->getId());

                if ($quoteAddressItem = $quoteAddress->getItemByQuoteItemId($quoteItemId)) {
                    $quoteAddressItem->setQty((int)$quoteAddressItem->getQty()+$qty);
                }
                else {
                    $quoteAddressItem = Mage::getModel('sales/quote_address_item')
                        ->importQuoteItem($quoteItem)
                        ->setQty($qty);
                    $quoteAddress->addItem($quoteAddressItem);
                }
                /**
                 * Collect rates for shipping method page only
                 */
                //$quoteAddress->setCollectShippingRates(true);
                $quoteAddress->setCollectShippingRates((boolean) $this->getCollectRatesFlag());
            }
        }
        return $this;
    }

    public function updateQuoteCustomerShippingAddress($addressId)
    {
        if ($address = $this->getCustomer()->getAddressById($addressId)) {
            $address->setCollectShippingRates(true);
            $this->getQuote()->getShippingAddressByCustomerAddressId($addressId)
                ->importCustomerAddress($address)
                ->collectTotals();
            $this->getQuote()->save();
        }
        return $this;
    }

    public function setQuoteCustomerBillingAddress($addressId)
    {
        if ($address = $this->getCustomer()->getAddressById($addressId)) {
            $this->getQuote()->getBillingAddress($addressId)
                ->importCustomerAddress($address)
                ->collectTotals();
            $this->getQuote()->save();
        }
        return $this;
    }

    public function setShippingMethods($methods)
    {
        $addresses = $this->getQuote()->getAllShippingAddresses();
        foreach ($addresses as $address) {
            if (isset($methods[$address->getId()])) {
                $address->setShippingMethod($methods[$address->getId()]);
            }
            elseif (!$address->getShippingMethod()) {
                Mage::throwException(Mage::helper('checkout')->__('Please select shipping methods for all addresses'));
            }
        }
        $addresses = $this->getQuote()
            ->collectTotals()
            ->save();
        return $this;
    }

    public function setPaymentMethod($payment)
    {
        if (!isset($payment['method'])) {
            Mage::throwException(Mage::helper('checkout')->__('Payment method is not defined'));
        }
        $this->getQuote()->getPayment()
            ->importData($payment)
            ->save();
        return $this;
    }

    protected function _prepareOrder($address)
    {
        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $order = $convertQuote->addressToOrder($address);
        $order->setBillingAddress(
            $convertQuote->addressToOrderAddress($this->getQuote()->getBillingAddress())
        );
        $order->setShippingAddress($convertQuote->addressToOrderAddress($address));
        $order->setPayment($convertQuote->paymentToOrderPayment($this->getQuote()->getPayment()));

        foreach ($address->getAllItems() as $item) {
            $item->setDescription(
                Mage::helper('checkout')->getQuoteItemProductDescription($item)
            );
            $order->addItem($convertQuote->itemToOrderItem($item));
        }

        return $order;
    }

    protected function _validate()
    {
        $helper = Mage::helper('checkout');
        if (!$this->getQuote()->getIsMultiShipping()) {
            Mage::throwException($helper->__('Invalid checkout type.'));
        }

        $addresses = $this->getQuote()->getAllShippingAddresses();
        foreach ($addresses as $address) {
            $addressValidation = $address->validate();
            if ($addressValidation !== true) {
                Mage::throwException($helper->__('Please check shipping addresses information.'));
            }
        	$method= $address->getShippingMethod();
        	$rate  = $address->getShippingRateByCode($method);
        	if (!$method || !$rate) {
        	    Mage::throwException($helper->__('Please specify shipping methods for all addresses.'));
        	}
        }
        $addressValidation = $this->getQuote()->getBillingAddress()->validate();
        if ($addressValidation !== true) {
            Mage::throwException($helper->__('Please check billing address information.'));
        }
        return $this;
    }

    public function createOrders()
    {
        $orderIds = array();
        $this->_validate();
        $shippingAddresses = $this->getQuote()->getAllShippingAddresses();
        $orders = array();
        foreach ($shippingAddresses as $address) {
            $order = $this->_prepareOrder($address);

            $orders[] = $order;
            Mage::dispatchEvent(
                'checkout_type_multishipping_create_orders_single',
                array('order'=>$order, 'address'=>$address)
            );
        }

        foreach ($orders as $order) {
            #$order->save();
            $order->place();
            $order->save();

            $order->sendNewOrderEmail();
            $orderIds[] = $order->getIncrementId();
        }

        Mage::getSingleton('core/session')->setOrderIds($orderIds);
        $this->getQuote()
            ->setIsActive(false)
            ->save();

        return $this;
    }

    public function save()
    {
        $this->getQuote()->collectTotals()
            ->save();
        return $this;
    }

    public function reset()
    {
        $this->getCheckoutSession()->setCheckoutState(Mage_Checkout_Model_Session::CHECKOUT_STATE_BEGIN);
        return $this;
    }
}
