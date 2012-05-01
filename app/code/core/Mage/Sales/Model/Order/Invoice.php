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


class Mage_Sales_Model_Order_Invoice extends Mage_Core_Model_Abstract
{
    /**
     * Invoice states
     */
    const STATE_OPEN       = 1;
    const STATE_PAID       = 2;
    const STATE_CANCELED   = 3;

    const XML_PATH_EMAIL_TEMPLATE   = 'sales_email/invoice/template';
    const XML_PATH_EMAIL_IDENTITY   = 'sales_email/invoice/identity';
    const XML_PATH_EMAIL_COPY_TO    = 'sales_email/invoice/copy_to';
    const XML_PATH_UPDATE_EMAIL_TEMPLATE= 'sales_email/invoice_comment/template';
    const XML_PATH_UPDATE_EMAIL_IDENTITY= 'sales_email/invoice_comment/identity';
    const XML_PATH_UPDATE_EMAIL_COPY_TO = 'sales_email/invoice_comment/copy_to';

    protected static $_states;

    protected $_items;
    protected $_comments;
    protected $_order;

    protected $_saveBeforeDestruct = false;

    protected $_eventPrefix = 'sales_order_invoice';
    protected $_eventObject = 'invoice';

    public function __destruct()
    {
        if ($this->_saveBeforeDestruct) {
            $this->save();
        }
    }

    /**
     * Initialize invoice resource model
     */
    protected function _construct()
    {
        $this->_init('sales/order_invoice');
    }

    /**
     * Retrieve invoice configuration model
     *
     * @return Mage_Sales_Model_Order_Invoice_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('sales/order_invoice_config');
    }

    /**
     * Retrieve store model instance
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->getOrder()->getStore();
    }

    /**
     * Declare order for invoice
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  Mage_Sales_Model_Order_Invoice
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        $this->setOrderId($order->getId())
            ->setStoreId($order->getStoreId());
        return $this;
    }

    /**
     * Retrieve the order the invoice for created for
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order instanceof Mage_Sales_Model_Order) {
            $this->_order = Mage::getModel('sales/order')->load($this->getOrderId());
        }
        return $this->_order;
    }

    /**
     * Retrieve billing address
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getBillingAddress()
    {
        return $this->getOrder()->getBillingAddress();
    }

    /**
     * Retrieve shipping address
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getShippingAddress()
    {
        return $this->getOrder()->getShippingAddress();
    }

    /**
     * Check invoice cancel state
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getState() == self::STATE_CANCELED;
    }

    /**
     * Check invice capture action availability
     *
     * @return bool
     */
    public function canCapture()
    {
        if ($this->getState() != self::STATE_CANCELED &&
            $this->getState() != self::STATE_PAID &&
            $this->getOrder()->getPayment()->canCapture()) {
            return true;
        }
        return false;
    }

    /**
     * Check invice void action availability
     *
     * @return bool
     */
    public function canVoid()
    {
        $canVoid = false;
        if ($this->getState() == self::STATE_PAID) {
            $canVoid = $this->getCanVoidFlag();
            /**
             * If we not retrieve negative answer from payment yet
             */
            if (is_null($canVoid)) {
                $canVoid = $this->getOrder()->getPayment()->canVoid($this);
                if ($canVoid === false) {
                    $this->setCanVoidFlag(false);
                    $this->_saveBeforeDestruct = true;
                }
            }
            else {
                $canVoid = (bool) $canVoid;
            }
        }
        return $canVoid;
    }

    /**
     * Check invice cancel action availability
     *
     * @return bool
     */
    public function canCancel()
    {
        return $this->getState() == self::STATE_OPEN;
    }

    /**
     * Capture invoice
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function capture()
    {
        $this->getOrder()->getPayment()->capture($this);
        $this->pay();
        return $this;
    }

    /**
     * Pay invoice
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function pay()
    {
        $this->setState(self::STATE_PAID);
        $this->getOrder()->getPayment()->pay($this);
        $this->getOrder()->setTotalPaid(
            $this->getOrder()->getTotalPaid()+$this->getGrandTotal()
        );
        $this->getOrder()->setBaseTotalPaid(
            $this->getOrder()->getBaseTotalPaid()+$this->getBaseGrandTotal()
        );
        Mage::dispatchEvent('sales_order_invoice_pay', array($this->_eventObject=>$this));
        return $this;
    }

    /**
     * Void invoice
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function void()
    {
        $this->getOrder()->getPayment()->void($this);
        $this->cancel();
        return $this;
    }

    /**
     * Cancel invoice action
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function cancel()
    {
        $this->setState(self::STATE_CANCELED);
        $this->getOrder()->getPayment()->cancelInvoice($this);
        foreach ($this->getAllItems() as $item) {
            $item->cancel();
        }
        $this->getOrder()->setTotalPaid(
            $this->getOrder()->getTotalPaid()-$this->getGrandTotal()
        );
        $this->getOrder()->setBaseTotalPaid(
            $this->getOrder()->getBaseTotalPaid()-$this->getBaseGrandTotal()
        );

        $this->getOrder()->setTotalInvoiced(
            $this->getOrder()->getTotalInvoiced()-$this->getGrandTotal()
        );
        $this->getOrder()->setBaseTotalInvoiced(
            $this->getOrder()->getBaseTotalInvoiced()-$this->getBaseGrandTotal()
        );
        $this->getOrder()->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
        Mage::dispatchEvent('sales_order_invoice_cancel', array($this->_eventObject=>$this));
        return $this;
    }

    /**
     * Invoice totals collecting
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function collectTotals()
    {
        foreach ($this->getConfig()->getTotalModels() as $model) {
            $model->collect($this);
        }
        return $this;
    }

    public function getItemsCollection()
    {
        if (empty($this->_items)) {
            $this->_items = Mage::getResourceModel('sales/order_invoice_item_collection')
                ->addAttributeToSelect('*')
                ->setInvoiceFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_items as $item) {
                    $item->setInvoice($this);
                }
            }
        }
        return $this->_items;
    }

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

    public function getItemById($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getId()==$itemId) {
                return $item;
            }
        }
        return false;
    }

    public function addItem(Mage_Sales_Model_Order_Invoice_Item $item)
    {
        $item->setInvoice($this)
            ->setParentId($this->getId())
            ->setStoreId($this->getStoreId());

        if (!$item->getId()) {
            $this->getItemsCollection()->addItem($item);
        }
        return $this;
    }

    /**
     * Retrieve invoice states array
     *
     * @return array
     */
    public static function getStates()
    {
        if (is_null(self::$_states)) {
            self::$_states = array(
                self::STATE_OPEN       => Mage::helper('sales')->__('Pending'),
                self::STATE_PAID       => Mage::helper('sales')->__('Paid'),
                self::STATE_CANCELED   => Mage::helper('sales')->__('Canceled'),
            );
        }
        return self::$_states;
    }

    /**
     * Retrieve invoice state name by state identifier
     *
     * @param   int $stateId
     * @return  string
     */
    public function getStateName($stateId = null)
    {
        if (is_null($stateId)) {
            $stateId = $this->getState();
        }

        if (is_null(self::$_states)) {
            self::getStates();
        }
        if (isset(self::$_states[$stateId])) {
            return self::$_states[$stateId];
        }
        return Mage::helper('sales')->__('Unknown State');
    }

    /**
     * Register invoice
     *
     * Apply to order, order items etc.
     *
     * @return unknown
     */
    public function register()
    {
        if ($this->getId()) {
            Mage::throwException(
                Mage::helper('sales')->__('Can not register existing invoice')
            );
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getQty()>0) {
                $item->register();
            }
            else {
                $item->isDeleted(true);
            }
        }

        if ($this->canCapture()) {
            if ($this->getCaptureRequested()) {
                $this->capture();
            }
        }
        elseif(!$this->getOrder()->getPayment()->getMethodInstance()->isGateway()) {
            $this->pay();
        }

        $this->getOrder()->setTotalInvoiced(
            $this->getOrder()->getTotalInvoiced()+$this->getGrandTotal()
        );
        $this->getOrder()->setBaseTotalInvoiced(
            $this->getOrder()->getBaseTotalInvoiced()+$this->getBaseGrandTotal()
        );

        $this->getOrder()->setSubtotalInvoiced(
            $this->getOrder()->getSubtotalInvoiced()+$this->getSubtotal()
        );
        $this->getOrder()->setBaseSubtotalInvoiced(
            $this->getOrder()->getBaseSubtotalInvoiced()+$this->getBaseSubtotal()
        );

        $this->getOrder()->setTaxInvoiced(
            $this->getOrder()->getTaxInvoiced()+$this->getTaxAmount()
        );
        $this->getOrder()->setBaseTaxInvoiced(
            $this->getOrder()->getBaseTaxInvoiced()+$this->getBaseTaxAmount()
        );

        $this->getOrder()->setShippingInvoiced(
            $this->getOrder()->getShippingInvoiced()+$this->getShippingAmount()
        );
        $this->getOrder()->setBaseShippingInvoiced(
            $this->getOrder()->getBaseShippingInvoiced()+$this->getBaseShippingAmount()
        );

        $state = $this->getState();
        if (is_null($state)) {
            $this->setState(self::STATE_OPEN);
        }
        return $this;
    }

    /**
     * Checking if the invoice is last
     *
     * @return bool
     */
    public function isLast()
    {
        foreach ($this->getAllItems() as $item) {
            if (!$item->isLast()) {
                return false;
            }
        }
        return true;
    }

    public function addComment($comment, $notify=false)
    {
        if (!($comment instanceof Mage_Sales_Model_Order_Invoice_Comment)) {
            $comment = Mage::getModel('sales/order_invoice_comment')
                ->setComment($comment)
                ->setIsCustomerNotified($notify);
        }
        $comment->setInvoice($this)
            ->setStoreId($this->getStoreId())
            ->setParentId($this->getId());
        if (!$comment->getId()) {
            $this->getCommentsCollection()->addItem($comment);
        }
        return $this;
    }

    public function getCommentsCollection()
    {
        if (is_null($this->_comments)) {
            $this->_comments = Mage::getResourceModel('sales/order_invoice_comment_collection')
                ->addAttributeToSelect('*')
                ->setInvoiceFilter($this->getId());
            if ($this->getId()) {
                foreach ($this->_comments as $comment) {
                    $comment->setInvoice($this);
                }
            }
        }
        return $this->_comments;
    }

    /**
     * Sending email with Invoice data
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function sendEmail($notifyCustomer=true, $comment='')
    {
        $order  = $this->getOrder();
        $bcc    = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);

        if (!$notifyCustomer && !$bcc) {
            return $this;
        }
        $paymentBlock   = Mage::helper('payment')->getInfoBlock($order->getPayment());

        $mailTemplate = Mage::getModel('core/email_template');

        if ($notifyCustomer) {
            $customerEmail = $order->getCustomerEmail();
            $mailTemplate->addBcc($bcc);
        }
        else {
            $customerEmail = $bcc;
        }

        $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$order->getStoreId()))
            ->sendTransactional(
                Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $order->getStoreId()),
                Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $order->getStoreId()),
                $customerEmail,
                $order->getBillingAddress()->getName(),
                array(
                    'order'       => $order,
                    'invoice'     => $this,
                    'comment'     => $comment,
                    'billing'     => $order->getBillingAddress(),
                    'payment_html'=> $paymentBlock->toHtml(),
                )
            );
        return $this;
    }

    /**
     * Sending email with invoice update information
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function sendUpdateEmail($notifyCustomer=true, $comment='')
    {
        $bcc = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
        if (!$notifyCustomer && !$bcc) {
            return $this;
        }

        $mailTemplate = Mage::getModel('core/email_template');
        if ($notifyCustomer) {
            $customerEmail = $this->getOrder()->getCustomerEmail();
            $mailTemplate->addBcc($bcc);
        }
        else {
            $customerEmail = $bcc;
        }


        $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$this->getStoreId()))
            ->sendTransactional(
                Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $this->getStoreId()),
                Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $this->getStoreId()),
                $customerEmail,
                $this->getOrder()->getBillingAddress()->getName(),
                array(
                    'order'  => $this->getOrder(),
                    'billing'=> $this->getOrder()->getBillingAddress(),
                    'invoice'=> $this,
                    'comment'=> $comment
                )
            );
        return $this;
    }

    protected function _getEmails($configPath)
    {
        $data = Mage::getStoreConfig($configPath, $this->getStoreId());
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }
}