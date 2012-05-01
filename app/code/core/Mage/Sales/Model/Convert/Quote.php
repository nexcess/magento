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
 * Order data convert model
 *
 * EVENTS
 *
 *
 * @category   Mage
 * @package    Mage_Sales
 */
class Mage_Sales_Model_Convert_Quote extends Varien_Object
{

    /**
     * Convert quote model to order model
     *
     * @param   Mage_Sales_Model_Quote $quote
     * @return  Mage_Sales_Model_Order
     */
    public function toOrder(Mage_Sales_Model_Quote $quote, $order=null)
    {
        if (!($order instanceof Mage_Sales_Model_Order)) {
            $order = Mage::getModel('sales/order');
        }
        /* @var $order Mage_Sales_Model_Order */

        $order
            /**
             * Base Data
             */
            ->setStoreId($quote->getStoreId())
            ->setQuoteId($quote->getId())

            ->setRemoteIp($quote->getRemoteIp())

            /**
             * Customer data
             */
            ->setCustomerId($quote->getCustomerId())
            ->setCustomerEmail($quote->getCustomerEmail())
            ->setCustomerFirstname($quote->getCustomerFirstname())
            ->setCustomerLastname($quote->getCustomerLastname())
            ->setCustomerGroupId($quote->getCustomerGroupId())
            ->setCustomerTaxClassId($quote->getCustomerTaxClassId())
            ->setCustomerNote($quote->getCustomerNote())
            ->setCustomerNoteNotify($quote->getCustomerNoteNotify())
            ->setCustomerIsGuest($quote->getCustomerIsGuest())

            /**
             * Currency data
             */
            ->setBaseCurrencyCode($quote->getBaseCurrencyCode())
            ->setStoreCurrencyCode($quote->getStoreCurrencyCode())
            ->setOrderCurrencyCode($quote->getQuoteCurrencyCode())
            ->setStoreToBaseRate($quote->getStoreToBaseRate())
            ->setStoreToOrderRate($quote->getStoreToQuoteRate())

            /**
             * Another data
             */
            ->setCouponCode($quote->getCouponCode())
            ->setGiftcertCode($quote->getGiftcertCode())
            ->setIsVirtual($quote->getIsVirtual())
            ->setIsMultiPayment($quote->getIsMultiPayment())
            ->setAppliedRuleIds($quote->getAppliedRuleIds());


        Mage::dispatchEvent('sales_convert_quote_to_order', array('order'=>$order, 'quote'=>$quote));
        return $order;
    }

    /**
     * Convert quote address model to order
     *
     * @param   Mage_Sales_Model_Quote $quote
     * @return  Mage_Sales_Model_Order
     */
    public function addressToOrder(Mage_Sales_Model_Quote_Address $address, $order=null)
    {
        if (!($order instanceof Mage_Sales_Model_Order)) {
            $order = $this->toOrder($address->getQuote());
        }

        $order
            ->setWeight($address->getWeight())
            ->setShippingMethod($address->getShippingMethod())
            ->setShippingDescription($address->getShippingDescription())
            ->setShippingRate($address->getShippingRate())

            ->setSubtotal($address->getSubtotal())
            ->setTaxAmount($address->getTaxAmount())
            ->setDiscountAmount($address->getDiscountAmount())
            ->setShippingAmount($address->getShippingAmount())
            ->setGiftcertAmount($address->getGiftcertAmount())
            ->setCustbalanceAmount($address->getCustbalanceAmount())
            ->setGrandTotal($address->getGrandTotal())

            ->setBaseSubtotal($address->getBaseSubtotal())
            ->setBaseTaxAmount($address->getBaseTaxAmount())
            ->setBaseDiscountAmount($address->getBaseDiscountAmount())
            ->setBaseShippingAmount($address->getBaseShippingAmount())
            ->setBaseGiftcertAmount($address->getBaseGiftcertAmount())
            ->setBaseCustbalanceAmount($address->getBaseCustbalanceAmount())
            ->setBaseGrandTotal($address->getBaseGrandTotal());

        Mage::dispatchEvent('sales_convert_quote_address_to_order', array('address'=>$address, 'order'=>$order));
        return $order;
    }

    /**
     * Convert quote address to order address
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Order_Address
     */
    public function addressToOrderAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $orderAddress = Mage::getModel('sales/order_address')
            ->setStoreId($address->getStoreId())
            ->setAddressType($address->getAddressType())
            ->setCustomerId($address->getCustomerId())
            ->setCustomerAddressId($address->getCustomerAddressId())
            ->setFirstname($address->getFirstname())
            ->setLastname($address->getLastname())
            ->setCompany($address->getCompany())
            ->setStreet($address->getStreet(-1))
            ->setCity($address->getCity())
            ->setRegion($address->getRegion())
            ->setRegionId($address->getRegionId())
            ->setPostcode($address->getPostcode())
            ->setCountryId($address->getCountryId())
            ->setTelephone($address->getTelephone())
            ->setFax($address->getFax());

        return $orderAddress;
    }

    /**
     * Convert quote payment to order payment
     *
     * @param   Mage_Sales_Model_Quote_Payment $payment
     * @return  Mage_Sales_Model_Quote_Payment
     */
    public function paymentToOrderPayment(Mage_Sales_Model_Quote_Payment $payment)
    {
        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($payment->getStoreId())
            ->setCustomerPaymentId($payment->getCustomerPaymentId())
            ->setMethod($payment->getMethod())
            ->setAdditionalData($payment->getAdditionalData())
            ->setPoNumber($payment->getPoNumber())
            ->setCcType($payment->getCcType())
            ->setCcNumberEnc($payment->getCcNumberEnc())
            ->setCcLast4($payment->getCcLast4())
            ->setCcOwner($payment->getCcOwner())
            ->setCcExpMonth($payment->getCcExpMonth())
            ->setCcExpYear($payment->getCcExpYear())

            ->setCcNumber($payment->getCcNumber()) // only for doing first transaction, not for save
            ->setCcCid($payment->getCcCid()) // only for doing first transaction, not for save
            ;
        return $orderPayment;
    }

    /**
     * Convert quote item to order item
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     * @return  Mage_Sales_Model_Order_Item
     */
    public function itemToOrderItem(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $orderItem = Mage::getModel('sales/order_item')
            ->setStoreId($item->getStoreId())
            ->setQuoteItemId($item->getId())
            ->setProductId($item->getProductId())
            ->setSuperProductId($item->getSuperProductId())
            ->setParentProductId($item->getParentProductId())
            ->setSku($item->getSku())
            ->setName($item->getName())
            ->setDescription($item->getDescription())
            ->setWeight($item->getWeight())
            ->setIsQtyDecimal($item->getIsQtyDecimal())
            ->setQtyOrdered($item->getQty())
            ->setOriginalPrice($item->getOriginalPrice())
            ->setAppliedRuleIds($item->getAppliedRuleIds())
            ->setAdditionalData($item->getAdditionalData())

            ->setPrice($item->getCalculationPrice())
            ->setTaxPercent($item->getTaxPercent())
            ->setTaxAmount($item->getTaxAmount())
            ->setRowWeight($item->getRowWeight())
            ->setRowTotal($item->getRowTotal())

            ->setBasePrice($item->getBaseCalculationPrice())
            ->setBaseOriginalPrice($item->getPrice())
            ->setBaseTaxAmount($item->getBaseTaxAmount())
            ->setBaseRowTotal($item->getBaseRowTotal());

        if (!$item->getNoDiscount()) {
            $orderItem->setDiscountPercent($item->getDiscountPercent())
                ->setDiscountAmount($item->getDiscountAmount())
                ->setBaseDiscountAmount($item->getBaseDiscountAmount());
        }

        Mage::dispatchEvent('sales_convert_quote_item_to_order_item',
            array('order_item'=>$orderItem, 'item'=>$item)
        );
        return $orderItem;
    }
}
