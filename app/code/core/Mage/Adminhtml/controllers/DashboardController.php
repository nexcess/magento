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
 * Dashboard admin controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_DashboardController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('dashboard');
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Dashboard'), Mage::helper('adminhtml')->__('Dashboard'));
        $this->_addContent($this->getLayout()->createBlock('adminhtml/dashboard', 'dashboard'));
        $this->renderLayout();
    }

/**
    public function configureAction()
    {
        $section = $this->getRequest()->getParam('section');
        $data = Mage::getSingleton('adminhtml/session')->getDashboardData();
        $data[$section] = $this->getRequest()->getPost();
        Mage::getSingleton('adminhtml/session')->setDashboardData($data);

        $this->_redirectReferer();
    }

    public function onlineAction()
    {
       $collection = Mage::getResourceSingleton('log/visitor_collection')
                   ->useOnlineFilter()
                   ->load();

       foreach( $collection -> getItems() as $item ) {
            $item->setLocation(long2ip($item->getRemoteAddr()))
                 ->addCustomerData($item);

            if( $item->getCustomerId() > 0 ) {
                //print_r( $item );
                $item->setFullName( $item->getCustomerData()->getName() );

                // Not needed yet...
                // $adresses = $item->getCustomerData()->getAddressCollection()->getPrimaryAddresses();
            } else {
                $item->setFullName('Guest');
            }
       }

       $this->getResponse()->setBody( $collection->toXml() );
    }


    public function visitorsAction()
    {
        $range = $this->getRequest()->getParam('range');

        $start = strtotime($range['start']);
        $end = strtotime($range['end']);
        $period = $end - $start;

        $detailPeriod = 60*60;

        if( $period > 2*24*60*60 )  {
            $detailPeriod = 24*60*60;
        }

        if( $period > 28*24*60*60 )  {
            $detailPeriod = 7*24*60*60;
        }

        if( $period > 365*24*60*60 )  {
            $detailPeriod = 30*24*60*60;
        }

        $allData = new Varien_Object();
        $allData->setMinimum(date('Y/m/d H:i', $start ));
        $allData->setMaximum(date('Y/m/d H:i', $end ));

        $logItem = new Varien_Object();

        $logXML = '';

        for($i = $start; $i < $end; $i += $detailPeriod) {
            $visitors  = rand( 1, 100000 );
            $customers = rand( 0, $visitors);
            $logItem->addData(array(
                'time'      => date('Y/m/d H:i', $i),
                'customers' => $customers,
                'visitors'  => $visitors
            ));

            $logXML.= $logItem->toXml(array(), 'item', false, true);
        }

        $allData->setItems($logXML);

        $this->getResponse()->setBody($allData->toXml(array(),'dataSource',true,false));

        /*
        $collection = Mage::getResourceSingleton('log/visitor_collection')
                    ->getStatistics('d')
                    ->applyDateRange($range['start'], $range['end'])
                    ->load();


        $this->getResponse()->setBody($collection->toXml());
        * /
    }

    public function visitorsLiveAction()
    {
        $minimum = time() - 12 * 60 * 60;
        $maximum = time() +  15 * 60;

        $allData = new Varien_Object();
        $allData->setMinimum(date('Y/m/d H:i', $minimum ));
        $allData->setMaximum(date('Y/m/d H:i', $maximum ));

        $logItem = new Varien_Object();

        $logXML = '';

       /* // Last 11 hours by hours
        for($i = $minimum; $i < $maximum - ( 15*60 + 60*60 ); $i += 60*60) {
            $visitors  = rand( 1, 100000 );
            $customers = rand( 0, $visitors);
            $logItem->addData(array(
                'time'      => date('Y/m/d H:i', $i),
                'customers' => $customers,
                'visitors'  => $visitors
            ));

            $logXML.= $logItem->toXml(array(), 'item', false, true);
        }* /

        // Last 12 hours by 5 minutes
        for($i = $minimum; $i < time(); $i += 5*60) {
            $visitors  = rand( 1, 100000 );
            $customers = rand( 0, $visitors);
            $logItem->addData(array(
                'time'      => date('Y/m/d H:i', $i),
                'customers' => $customers,
                'visitors'  => $visitors
            ));

            $logXML.= $logItem->toXml(array(), 'item', false, true);
        }

        $allData->setItems($logXML);

        $this->getResponse()->setBody($allData->toXml(array(),'dataSource',true,false));
    }

    public function visitorsReportAction()
    {
        // Not implemented yet
        $this->getResponse()->setBody( 'Not implemented yet' );
    }

    public function visitorsLiveUpdateAction()
    {
        $minimum = time() - 12 * 60 * 60;
        $maximum = time() +  15 * 60;

        $visitors = rand( 1, 100000 );
        $customers = rand( 0, $visitors);

        $updateData = new Varien_Object();
        $updateData->setMinimum(date('Y/m/d H:i', $minimum ));
        $updateData->setMaximum(date('Y/m/d H:i', $maximum ));
        $updateData->setTime(date('Y/m/d H:i', time() ));
        $updateData->setCustomers($customers);
        $updateData->setVisitors($visitors);

        $this->getResponse()->setBody($updateData->toXml(array(),'update',true,false));
    }

    public function quoteAction()
    {
        $quote = Mage::getModel('sales/quote')
               ->load($this->getRequest()->getParam('quoteId',0));



        $itemsFilter = new Varien_Filter_Object_Grid();
        $itemsFilter->addFilter(new Varien_Filter_Sprintf('%d'), 'qty');
        $itemsFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'price');
        $itemsFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'row_total');
        $cartItems = $itemsFilter->filter($quote->getItems());

        $totalsFilter = new Varien_Filter_Array_Grid();
        $totalsFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'value');
        $cartTotals = $totalsFilter->filter($quote->getTotals());

        // Creating XML response.
        // In future would be good if it will be in some collection.
        $itemsXML = "";

        $itemObject = new Varien_Object();
        $xmlObject = new Varien_Object();

        foreach( $cartItems as $cartItem ) {
            $itemObject->addData($cartItem);
            $itemObject->setUrl($this->getUrl('catalog/product/view', array('id'=>$itemObject->getProductId())));
            $itemsXML.= $itemObject->toXml(array('price', 'qty', 'row_total', 'name', 'url'), "item", false, true);
        }

        $xmlObject->setItems( $itemsXML );

        $totalXML = "";
        $totalObject = new Varien_Object();

        foreach( $cartTotals as $cartTotal ) {
            $totalObject->addData( $cartTotal );
            $totalXML.= $totalObject->toXml(array('title','value'), 'total', false, true);
        }

        $xmlObject->setTotals( $totalXML );
        $this->getResponse()->setBody( $xmlObject->toXml(array(), 'dataSource', true, false) );
    }
*/

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('dashboard');
    }
}