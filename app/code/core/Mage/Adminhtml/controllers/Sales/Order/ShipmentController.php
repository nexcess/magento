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
 * Adminhtml sales order shipment controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Controller_Action
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
        $data = $this->getRequest()->getParam('shipment');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        }
        else {
            $qtys = array();
        }
        return $qtys;
    }

    /**
     * Initialize shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    protected function _initShipment()
    {
        $shipment = false;
        if ($shipmentId = $this->getRequest()->getParam('shipment_id')) {
            $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        }
        elseif ($orderId = $this->getRequest()->getParam('order_id')) {
            $order      = Mage::getModel('sales/order')->load($orderId);

            /**
             * Check order existing
             */
            if (!$order->getId()) {
                $this->_getSession()->addError($this->__('Order not longer exist.'));
                return false;
            }
            /**
             * Check shipment create availability
             */
            if (!$order->canShip()) {
                $this->_getSession()->addError($this->__('Can not do shipment for order.'));
                return false;
            }

            $convertor  = Mage::getModel('sales/convert_order');
            $shipment    = $convertor->toShipment($order);

            $savedQtys = $this->_getItemQtys();
            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip()) {
                    continue;
                }
                $item = $convertor->itemToShipmentItem($orderItem);
                if (isset($savedQtys[$orderItem->getId()])) {
                    $qty = $savedQtys[$orderItem->getId()];
                }
                else {
                    $qty = $orderItem->getQtyToShip();
                }
                $item->setQty($qty);
            	$shipment->addItem($item);
            }

            if ($tracks = $this->getRequest()->getPost('tracking')) {
                foreach ($tracks as $data) {
                	$track = Mage::getModel('sales/order_shipment_track')
                	   ->addData($data);
                    $shipment->addTrack($track);
                }
            }
        }

        Mage::register('current_shipment', $shipment);
        return $shipment;
    }

    protected function _saveShipment($shipment)
    {
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    /**
     * shipment information page
     */
    public function viewAction()
    {
        if ($shipment = $this->_initShipment()) {
            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->_addContent($this->getLayout()->createBlock('adminhtml/sales_order_shipment_view')->updateBackButtonUrl())
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Start create shipment action
     */
    public function startAction()
    {
        /**
         * Clear old values for shipment qty's
         */
        $this->_redirect('*/*/new', array('order_id'=>$this->getRequest()->getParam('order_id')));
    }

    /**
     * Shipment create page
     */
    public function newAction()
    {
        if ($shipment = $this->_initShipment()) {
            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->_addContent($this->getLayout()->createBlock('adminhtml/sales_order_shipment_create'))
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('shipment');

        try {
            if ($shipment = $this->_initShipment()) {
                $shipment->register();

                $comment = '';
                if (!empty($data['comment_text'])) {
                    $shipment->addComment($data['comment_text'], isset($data['comment_customer_notify']));
                    $comment = $data['comment_text'];
                }

                if (!empty($data['send_email'])) {
                    $shipment->setEmailSent(true);
                }

                $this->_saveShipment($shipment);
                $shipment->sendEmail(!empty($data['send_email']), $comment);
                $this->_getSession()->addSuccess($this->__('Shipment was successfully created.'));
                $this->_redirect('*/sales_order/view', array('order_id' => $shipment->getOrderId()));
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
            $this->_getSession()->addError($this->__('Can not save shipment.'));
        }
        $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
    }

    public function emailAction()
    {
        try {
            if ($shipment = $this->_initShipment()) {
                $shipment->sendEmail(true);
                $this->_getSession()->addSuccess($this->__('Shipment was successfully sent.'));
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('Can not send shipment information.'));
        }
        $this->_redirect('*/*/view', array(
            'shipment_id' => $this->getRequest()->getParam('shipment_id')
        ));
    }

    /**
     * Add new tracking number action
     */
    public function addTrackAction()
    {
        try {
            $carrier = $this->getRequest()->getPost('carrier');
            $number  = $this->getRequest()->getPost('number');
            $title  = $this->getRequest()->getPost('title');
            if (empty($carrier)) {
                Mage::throwException($this->__('You need specify carrier.'));
            }
            if (empty($number)) {
                Mage::throwException($this->__('Tracking number can not be empty.'));
            }
            if ($shipment = $this->_initShipment()) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($number)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);
                $shipment->addTrack($track)
                    ->save();
                $block = $this->getLayout()->createBlock('adminhtml/sales_order_shipment_view_tracking');
                $response = $block->toHtml();
            }
            else {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Can not initialize shipment for adding tracking number.'),
                );
            }
        }
        catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
            );
        }
        catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Can not add tracking number.'),
            );
        }
        if (is_array($response)) {
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }

    public function removeTrackAction()
    {
        $trackId    = $this->getRequest()->getParam('track_id');
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $track = Mage::getModel('sales/order_shipment_track')->load($trackId);
        if ($track->getId()) {
            try {
                if ($shipmentId = $this->_initShipment()) {
                    $track->delete();
                    $block = $this->getLayout()->createBlock('adminhtml/sales_order_shipment_view_tracking');
                    $response = $block->toHtml();
                }
                else {
                    $response = array(
                        'error'     => true,
                        'message'   => $this->__('Can not initialize shipment for delete tracking number.'),
                    );
                }
            }
            catch (Exception $e) {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Can not delete tracking number.'),
                );
            }
        }
        else {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Can not load track with retrieving identifier.'),
            );
        }
        if (is_array($response)) {
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }

    public function viewTrackAction()
    {
        $trackId    = $this->getRequest()->getParam('track_id');
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $track = Mage::getModel('sales/order_shipment_track')->load($trackId);
        if ($track->getId()) {
            try {
                $response = $track->getNumberDetail();
            }
            catch (Exception $e) {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Can not retrieve tracking number detail.'),
                );
            }
        }
        else {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Can not load track with retrieving identifier.'),
            );
        }

        if ( is_object($response)){
            $className = Mage::getConfig()->getBlockClassName('adminhtml/template');
            $block = new $className();
            $block->setType('adminhtml/template')
                ->setIsAnonymous(true)
                ->setTemplate('sales/order/shipment/tracking/info.phtml');

            $block->setTrackingInfo($response);

            $this->getResponse()->setBody($block->toHtml());
        }
        else {
            if (is_array($response)) {
                $response = Zend_Json::encode($response);
            }

            $this->getResponse()->setBody($response);
        }
    }

    public function printAction()
    {
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            if ($invoice = Mage::getModel('sales/order_shipment')->load($invoiceId)) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(array($invoice));
                $this->_prepareDownloadResponse('packingslip'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
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
                'shipment_id',
                $this->getRequest()->getParam('id')
            );
            $data = $this->getRequest()->getPost('comment');
            if (empty($data['comment'])) {
                Mage::throwException($this->__('Comment text field can not be empty.'));
            }
            $shipment = $this->_initShipment();
            $shipment->addComment($data['comment'], isset($data['is_customer_notified']));
            $shipment->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
            $shipment->save();

            $response = $this->getLayout()->createBlock('adminhtml/sales_order_comments_view')
                ->setEntity($shipment)
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