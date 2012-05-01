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
 * Adminhtml sales order edit controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Controller_Action
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
        $data = $this->getRequest()->getParam('invoice');
        if (isset($data['items'])) {
            $qtys = $data['items'];
            //$this->_getSession()->setInvoiceItemQtys($qtys);
        }
        /*elseif ($this->_getSession()->getInvoiceItemQtys()) {
        	$qtys = $this->_getSession()->getInvoiceItemQtys();
        }*/
        else {
            $qtys = array();
        }
        return $qtys;
    }

    /**
     * Initialize invoice model instance
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _initInvoice()
    {
        $invoice = false;
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
        }
        elseif ($orderId = $this->getRequest()->getParam('order_id')) {
            $order      = Mage::getModel('sales/order')->load($orderId);
            /**
             * Check order existing
             */
            if (!$order->getId()) {
                $this->_getSession()->addError($this->__('Order not longer exist'));
                return false;
            }
            /**
             * Check invoice create availability
             */
            if (!$order->canInvoice()) {
                $this->_getSession()->addError($this->__('Can not do invoice for order'));
                return false;
            }

            $convertor  = Mage::getModel('sales/convert_order');
            $invoice    = $convertor->toInvoice($order);

            $savedQtys = $this->_getItemQtys();
            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToInvoice()) {
                    continue;
                }
                $item = $convertor->itemToInvoiceItem($orderItem);
                if (isset($savedQtys[$orderItem->getId()])) {
                    $qty = $savedQtys[$orderItem->getId()];
                }
                else {
                    $qty = $orderItem->getQtyToInvoice();
                }
                $item->setQty($qty);
            	$invoice->addItem($item);
            }
            $invoice->collectTotals();
        }

        Mage::register('current_invoice', $invoice);
        return $invoice;
    }

    /**
     * Save data for invoice and related order
     *
     * @param   Mage_Sales_Model_Order_Invoice $invoice
     * @return  Mage_Adminhtml_Sales_Order_InvoiceController
     */
    protected function _saveInvoice($invoice)
    {
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return $this;
    }

    protected function _prepareShipment($invoice)
    {
        $convertor  = Mage::getModel('sales/convert_order');
        $shipment    = $convertor->toShipment($invoice->getOrder());

        foreach ($invoice->getAllItems() as $item) {
            $qty = min($item->getQty(), $item->getOrderItem()->getQtyToShip());
            $shipmentItem = $convertor->itemToShipmentItem($item->getOrderItem());
            $shipmentItem->setQty($qty);
            $shipment->addItem($shipmentItem);
        }
        $shipment->register();

        if ($tracks = $this->getRequest()->getPost('tracking')) {
            foreach ($tracks as $data) {
                $track = Mage::getModel('sales/order_shipment_track')
                ->addData($data);
                $shipment->addTrack($track);
            }
        }
        return $shipment;
    }

    /**
     * Invoice information page
     */
    public function viewAction()
    {
        if ($invoice = $this->_initInvoice()) {
            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->_addContent($this->getLayout()->createBlock('adminhtml/sales_order_invoice_view')->updateBackButtonUrl())
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Start create invoice action
     */
    public function startAction()
    {
        /**
         * Clear old values for invoice qty's
         */
        $this->_getSession()->getInvoiceItemQtys(true);
        $this->_redirect('*/*/new', array('order_id'=>$this->getRequest()->getParam('order_id')));
    }

    /**
     * Invoice create page
     */
    public function newAction()
    {
        if ($invoice = $this->_initInvoice()) {
            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->_addContent($this->getLayout()->createBlock('adminhtml/sales_order_invoice_create'))
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
            $invoice = $this->_initInvoice();
            $response = $this->getLayout()->createBlock('adminhtml/sales_order_invoice_create_items')
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
     * Save invoice
     * We can save only new invoice. Existing invoices are not editable
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('invoice');
        try {
            if ($invoice = $this->_initInvoice()) {

                if (!empty($data['do_capture'])) {
                    $invoice->setCaptureRequested(true);
                }

                if (!empty($data['comment_text'])) {
                    $invoice->addComment($data['comment_text'], isset($data['comment_customer_notify']));
                }

                $invoice->register();

                if (!empty($data['send_email'])) {
                    $invoice->setEmailSent(true);
                }

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $shipment = false;
                if (!empty($data['do_shipment'])) {
                    $shipment = $this->_prepareShipment($invoice);
                    $transactionSave->addObject($shipment);
                }
                $transactionSave->save();

                /**
                 * Sending emails
                 */
                $comment = '';
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
                $invoice->sendEmail(!empty($data['send_email']), $comment);
                if ($shipment) {
                    $shipment->sendEmail(!empty($data['send_email']));
                }

                $this->_getSession()->addSuccess($this->__('Invoice was successfully created'));

                $this->_redirect('*/sales_order/view', array('order_id' => $invoice->getOrderId()));
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
            $this->_getSession()->addError($this->__('Can not save invoice'));
        }

        $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
    }


    /**
     * Capture invoice action
     */
    public function captureAction()
    {
        if ($invoice = $this->_initInvoice()) {
            try {
                $invoice->capture();
                $this->_saveInvoice($invoice);
                $this->_getSession()->addSuccess($this->__('Invoice was successfully captured'));
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Invoice capture error'));
            }
            $this->_redirect('*/*/view', array('invoice_id'=>$invoice->getId()));
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Cancel invoice action
     */
    public function cancelAction()
    {
        if ($invoice = $this->_initInvoice()) {
            try {
                $invoice->cancel();
                $this->_saveInvoice($invoice);
                $this->_getSession()->addSuccess($this->__('Invoice was successfully canceled.'));
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Invoice cancel error.'));
            }
            $this->_redirect('*/*/view', array('invoice_id'=>$invoice->getId()));
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Void invoice action
     */
    public function voidAction()
    {
        if ($invoice = $this->_initInvoice()) {
            try {
                $invoice->void();
                $this->_saveInvoice($invoice);
                $this->_getSession()->addSuccess($this->__('Invoice was successfully voided'));
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Invoice void error'));
            }
            $this->_redirect('*/*/view', array('invoice_id'=>$invoice->getId()));
        }
        else {
            $this->_forward('noRoute');
        }
    }

    public function addCommentAction()
    {
        try {
            $this->getRequest()->setParam('invoice_id', $this->getRequest()->getParam('id'));
            $data = $this->getRequest()->getPost('comment');
            if (empty($data['comment'])) {
                Mage::throwException($this->__('Comment text field can not be empty.'));
            }
            $invoice = $this->_initInvoice();
            $invoice->addComment($data['comment'], isset($data['is_customer_notified']));
            $invoice->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
            $invoice->save();

            $response = $this->getLayout()->createBlock('adminhtml/sales_order_comments_view')
                ->setEntity($invoice)
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

    public function printAction()
    {
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            if ($invoice = Mage::getModel('sales/order_invoice')->load($invoiceId)) {
                $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
                $this->_prepareDownloadResponse('invoice'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }
}