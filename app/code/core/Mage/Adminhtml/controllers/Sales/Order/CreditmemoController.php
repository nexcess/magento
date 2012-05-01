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
 * Adminhtml sales order creditmemo controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Additional initialization
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Mage_Sales');
    }

    protected function _getItemQtys()
    {
        $data = $this->getRequest()->getParam('creditmemo');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        }
        else {
            $qtys = array();
        }
        return $qtys;
    }

    protected function _canCreditmemo($order)
    {
        /**
         * Check order existing
         */
        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('Order not longer exist'));
            return false;
        }

        /**
         * Check creditmemo create availability
         */
        if (!$order->canCreditmemo()) {
            $this->_getSession()->addError($this->__('Can not do credit memo for order'));
            return false;
        }
        return true;
    }

    /**
     * Initialize creditmemo model instance
     *
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    protected function _initCreditmemo()
    {
        $creditmemo = false;
        if ($creditmemoId = $this->getRequest()->getParam('creditmemo_id')) {
            $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
        }
        elseif ($orderId = $this->getRequest()->getParam('order_id')) {
            $data   = $this->getRequest()->getParam('creditmemo');
            $order  = Mage::getModel('sales/order')->load($orderId);
            $invoiceId = $this->getRequest()->getParam('invoice_id');
            $invoice= null;

            if (!$this->_canCreditmemo($order)) {
                return false;
            }

            if ($invoiceId) {
                $invoice = Mage::getModel('sales/order_invoice')
                    ->load($invoiceId)
                    ->setOrder($order);
            }

            $convertor  = Mage::getModel('sales/convert_order');
            $creditmemo = $convertor->toCreditmemo($order)
                ->setInvoice($invoice);

            $savedQtys = $this->_getItemQtys();

            if ($invoice && $invoice->getId()) {
                foreach ($invoice->getAllItems() as $invoiceItem) {
                    $orderItem = $invoiceItem->getOrderItem();
                    if (!$orderItem->getQtyToRefund()) {
                        continue;
                    }
                    $item = $convertor->itemToCreditmemoItem($orderItem);
                    if (isset($savedQtys[$orderItem->getId()])) {
                        $qty = $savedQtys[$orderItem->getId()];
                    }
                    else {
                        $qty = min($orderItem->getQtyToRefund(), $invoiceItem->getQty());
                    }
                    $item->setQty($qty);
                    $creditmemo->addItem($item);
                }
            } else {
                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToRefund()) {
                        continue;
                    }
                    $item = $convertor->itemToCreditmemoItem($orderItem);
                    if (isset($savedQtys[$orderItem->getId()])) {
                        $qty = $savedQtys[$orderItem->getId()];
                    }
                    else {
                        $qty = $orderItem->getQtyToRefund();
                    }
                    $item->setQty($qty);
                    $creditmemo->addItem($item);
                }
            }

            if (isset($data['shipping_amount'])) {
                $creditmemo->setShippingAmount($data['shipping_amount']);
            } elseif ($invoice) {
                $creditmemo->setShippingAmount($invoice->getShippingAmount());
            }

            if (isset($data['adjustment_positive'])) {
                $creditmemo->setAdjustmentPositive($data['adjustment_positive']);
            }
            if (isset($data['adjustment_negative'])) {
                $creditmemo->setAdjustmentNegative($data['adjustment_negative']);
            }

            $creditmemo->collectTotals();
        }

        Mage::register('current_creditmemo', $creditmemo);
        return $creditmemo;
    }

    protected function _saveCreditmemo($creditmemo)
    {
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();

        return $this;
    }

    /**
     * creditmemo information page
     */
    public function viewAction()
    {
        if ($creditmemo = $this->_initCreditmemo()) {
            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->_addContent($this->getLayout()->createBlock('adminhtml/sales_order_creditmemo_view')->updateBackButtonUrl())
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Start create creditmemo action
     */
    public function startAction()
    {
        /**
         * Clear old values for creditmemo qty's
         */
        $this->_redirect('*/*/new', array('_current'=>true));
    }

    /**
     * creditmemo create page
     */
    public function newAction()
    {
        if ($creditmemo = $this->_initCreditmemo()) {
            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->_addContent($this->getLayout()->createBlock('adminhtml/sales_order_creditmemo_create'))
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Update items qty action
     */
    public function updateQtyAction()
    {
        try {
            $creditmemo = $this->_initCreditmemo();
            $response = $this->getLayout()->createBlock('adminhtml/sales_order_creditmemo_create_items')
                ->toHtml();
        }
        catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage()
            );
            $response = Zend_Json::encode($response);
        }
        catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Can not update item qty')
            );
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }

    /**
     * Save creditmemo
     * We can save only new creditmemo. Existing creditmemos are not editable
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('creditmemo');
        try {
            if ($creditmemo = $this->_initCreditmemo()) {
                if ($creditmemo->getGrandTotal() <=0) {
                    Mage::throwException(
                        $this->__('Credit Memo total must be positive.')
                    );
                }
                $comment = '';
                if (!empty($data['comment_text'])) {
                    $comment = $data['comment_text'];
                    $creditmemo->addComment($data['comment_text'], isset($data['comment_customer_notify']));
                }

                if (isset($data['do_refund'])) {
                    $creditmemo->setRefundRequested(true);
                }
                if (isset($data['do_offline'])) {
                    $creditmemo->setOfflineRequested($data['do_offline']);
                }

                $creditmemo->register();
                if (!empty($data['send_email'])) {
                    $creditmemo->setEmailSent(true);
                }

                $this->_saveCreditmemo($creditmemo);
                $creditmemo->sendEmail(!empty($data['send_email']), $comment);
                $this->_getSession()->addSuccess($this->__('Creditmemo was successfully created'));
                $this->_redirect('*/sales_order/view', array('order_id' => $creditmemo->getOrderId()));
                return;
            }
            else {
                $this->_forward('noRoute');
                return;
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('Can not save creditmemo'));
        }
        $this->_redirect('*/*/new', array('_current' => true));
    }

    /**
     * Cancel creditmemo action
     */
    public function cancelAction()
    {
        if ($creditmemo = $this->_initCreditmemo()) {
            try {
                $creditmemo->cancel();
                $this->_saveCreditmemo($creditmemo);
                $this->_getSession()->addSuccess($this->__('Creditmemo was successfully canceled.'));
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Creditmemo cancel error.'));
            }
            $this->_redirect('*/*/view', array('creditmemo_id'=>$creditmemo->getId()));
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Void creditmemo action
     */
    public function voidAction()
    {
        if ($invoice = $this->_initCreditmemo()) {
            try {
                $creditmemo->void();
                $this->_saveCreditmemo($creditmemo);
                $this->_getSession()->addSuccess($this->__('Creditmemo was successfully voided'));
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Creditmemo void error'));
            }
            $this->_redirect('*/*/view', array('creditmemo_id'=>$creditmemo->getId()));
        }
        else {
            $this->_forward('noRoute');
        }
    }

    public function printAction()
    {
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            if ($invoice = Mage::getModel('sales/order_creditmemo')->load($invoiceId)) {
                $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf(array($invoice));
                $this->_prepareDownloadResponse('creditmemo'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }
    
    public function addCommentAction()
    {
        try {
            $this->getRequest()->setParam(
                'creditmemo_id',
                $this->getRequest()->getParam('id')
            );
            $data = $this->getRequest()->getPost('comment');
            if (empty($data['comment'])) {
                Mage::throwException($this->__('Comment text field can not be empty.'));
            }
            $creditmemo = $this->_initCreditmemo();
            $creditmemo->addComment($data['comment'], isset($data['is_customer_notified']));
            $creditmemo->save();
            $creditmemo->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);

            $response = $this->getLayout()->createBlock('adminhtml/sales_order_comments_view')
                ->setEntity($creditmemo)
                ->toHtml();
        }
        catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage()
            );
            $response = Zend_Json::encode($response);
        }
        catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Can not add new comment.')
            );
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }
}