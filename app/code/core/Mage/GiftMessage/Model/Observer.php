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
 * @package    Mage_GiftMessage
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Gift Message Observer Model
 *
 * @category   Mage
 * @package    Mage_GiftMessage
 */
class Mage_GiftMessage_Model_Observer extends Varien_Object
{

    /**
     * Set gift messages to order item on import item
     *
     * @param Varien_Object $observer
     * @return Mage_GiftMessage_Model_Observer
     */
    public function salesEventConvertQuoteItemToOrderItem($observer)
    {
        $observer->getEvent()->getOrderItem()
            ->setGiftMessageId($observer->getEvent()->getItem()->getGiftMessageId())
            ->setGiftMessageAvailable($this->_getAvailable($observer->getEvent()->getItem()->getProductId()));
        return $this;
    }

    /**
     * Set gift messages to order from quote address
     *
     * @param Varien_Object $observer
     * @return Mage_GiftMessage_Model_Observer
     */
    public function salesEventConvertQuoteAddressToOrder($observer)
    {
        if($observer->getEvent()->getAddress()->getGiftMessageId()) {
            $observer->getEvent()->getOrder()
                ->setGiftMessageId($observer->getEvent()->getAddress()->getGiftMessageId());
        }
        return $this;
    }

    /**
     * Set gift messages to order from quote address
     *
     * @param Varien_Object $observer
     * @return Mage_GiftMessage_Model_Observer
     */
    public function salesEventConvertQuoteToOrder($observer)
    {
        $observer->getEvent()->getOrder()
            ->setGiftMessageId($observer->getEvent()->getQuote()->getGiftMessageId());
        return $this;
    }

    /**
     * Geter for available gift messages value from product
     *
     * @param Mage_Catalog_Model_Product|integer $product
     * @return integer|null
     */
    protected function _getAvailable($product)
    {
        if(is_object($product)) {
            return $product->getGiftMessageAvailable();
        }
        return Mage::getModel('catalog/product')->load($product)->getGiftMessageAvailable();
    }

    /**
     * Operate with gift messages on checkout proccess
     *
     * @param Varieb_Object $observer
     * @return Mage_GiftMessage_Model_Observer
     */
    public function checkoutEventCreateGiftMessage($observer)
    {
        $giftMessages = $observer->getEvent()->getRequest()->getParam('giftmessage');
        if(is_array($giftMessages)) {
            foreach ($giftMessages as $entityId=>$message) {

                $giftMessage = Mage::getModel('giftmessage/message');
                $entity = $giftMessage->getEntityModelByType($message['type']);

                if ($message['type']=='quote') {
                    $entity->setStoreId(Mage::app()->getStore()->getId());
                }

                $entity->load($entityId);

                if($entity->getGiftMessageId()) {
                    $giftMessage->load($entity->getGiftMessageId());
                }

                if(trim($message['message'])=='') {
                    if($giftMessage->getId()) {
                        try{
                            $giftMessage->delete();
                            $entity->setGiftMessageId(0)
                                ->save();
                        }
                        catch (Exception $e) { }
                    }
                    continue;
                }

                try {
                    $giftMessage->setSender($message['from'])
                        ->setRecipient($message['to'])
                        ->setMessage($message['message'])
                        ->save();

                    $entity->setGiftMessageId($giftMessage->getId())
                        ->save();

                }
                catch (Exception $e) { }
            }
        }
        return $this;
    }

    /**
     * Set giftmessage available default value to product
     * on catalog products collection load
     *
     * @param Varien_Object $observer
     * @return Mage_GiftMessage_Model_Observer
     */
    public function catalogEventProductCollectionAfterLoad($observer)
    {
        $collection = $observer->getEvent()->getCollection();
        foreach ($collection as $item) {
            if($item->getGiftMessageAvailable()===null) {
                $item->setGiftMessageAvailable(2);
            }
        }
        return $this;
    }

    /**
     * Duplicates giftmessage from order to quote on import
     *
     * @param Varien_Object $observer
     * @return Mage_GiftMessage_Model_Observer
     */
    public function salesEventOrderToQuote($observer)
    {
        if($giftMessageId = $observer->getEvent()->getOrder()->getGiftMessageId()) {
            $giftMessage = Mage::getModel('giftmessage/message')->load($giftMessageId)
                ->setId(null)
                ->save();
            $observer->getEvent()->getQuote()->setGiftMessageId($giftMessage->getId());
        }

        return $this;
    }

}
