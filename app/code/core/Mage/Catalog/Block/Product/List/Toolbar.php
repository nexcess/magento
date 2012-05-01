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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product list toolbar
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Block_Product_List_Toolbar extends Mage_Page_Block_Html_Pager
{
    protected $_orderVarName        = 'order';
    protected $_directionVarName    = 'dir';
    protected $_modeVarName         = 'mode';
    protected $_availableOrder      = array();
    protected $_availableMode       = array();
    protected $_enableViewSwitcher  = true;
    protected $_isExpanded          = true;

    public function __construct()
    {
        parent::__construct();
        $this->_availableOrder = array(
            'position'  => Mage::helper('catalog')->__('Best Value'),
            'name'      => Mage::helper('catalog')->__('Name'),
            'price'     => Mage::helper('catalog')->__('Price')
        );

        switch (Mage::getStoreConfig('catalog/frontend/list_mode')) {
        	case 'grid':
		        $this->_availableMode = array('grid' => Mage::helper('catalog')->__('Grid'));
        		break;

        	case 'list':
		        $this->_availableMode = array('list' => Mage::helper('catalog')->__('List'));
        		break;

        	case 'grid-list':
		        $this->_availableMode = array('grid' => Mage::helper('catalog')->__('Grid'), 'list' =>  Mage::helper('catalog')->__('List'));
        		break;

        	case 'list-grid':
		        $this->_availableMode = array('list' => Mage::helper('catalog')->__('List'), 'grid' => Mage::helper('catalog')->__('Grid'));
        		break;
        }
        $this->setTemplate('catalog/product/list/toolbar.phtml');
    }

    public function setCollection($collection)
    {
        parent::setCollection($collection);
        if ($this->getCurrentOrder()) {
            $this->getCollection()->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
        }
        return $this;
    }

    public function getOrderVarName()
    {
        return $this->_orderVarName;
    }

    public function getDirectionVarName()
    {
        return $this->_directionVarName;
    }

    public function getModeVarName()
    {
        return $this->_modeVarName;
    }

    public function getCurrentOrder()
    {
        $order = $this->getRequest()->getParam($this->getOrderVarName());
        $orders = $this->getAvailableOrders();
        if ($order && isset($orders[$order])) {
            return $order;
        }
        $keys = array_keys($orders);
        return $keys[0];
    }

    public function getCurrentDirection()
    {
        if ($dir = (string) $this->getRequest()->getParam($this->getDirectionVarName())) {
            $dir = strtolower($dir);
            if (in_array($dir, array('asc', 'desc'))) {
                return $dir;
            }
        }
        return 'asc';
    }

    public function getAvailableOrders()
    {
        return $this->_availableOrder;
    }

    public function setAvailableOrders($orders)
    {
        $this->_availableOrder = $orders;
        return $this;
    }


    public function isOrderCurrent($order)
    {
        return $order == $this->getRequest()->getParam('order');
    }

    public function getOrderUrl($order, $direction)
    {
        if (is_null($order)) {
            $order = $this->getCurrentOrder() ? $this->getCurrentOrder() : $this->_availableOrder[0];
        }
        return $this->getPagerUrl(array(
            $this->getOrderVarName()=>$order,
            $this->getDirectionVarName()=>$direction
        ));
    }

    public function getCurrentMode()
    {
        $mode = $this->getRequest()->getParam($this->getModeVarName());
        if ($mode) {
            Mage::getSingleton('catalog/session')->setDisplayMode($mode);
        }
        else {
            $mode = Mage::getSingleton('catalog/session')->getDisplayMode();
        }

        if ($mode && isset($this->_availableMode[$mode])) {
            return $mode;
        }
        return current(array_keys($this->_availableMode));
    }

    public function isModeActive($mode)
    {
        return $this->getCurrentMode() == $mode;
    }

    public function getModes()
    {
        return $this->_availableMode;
    }

    public function setModes($modes)
    {
        if(!isset($this->_availableMode)){
            $this->_availableMode = $modes;
        }
        return $this;
    }

    public function getModeUrl($mode)
    {
        return $this->getPagerUrl(array($this->getModeVarName()=>$mode));
    }

    public function disableViewSwitcher()
    {
        $this->_enableViewSwitcher = false;
        return $this;
    }

    public function enableViewSwitcher()
    {
        $this->_enableViewSwitcher = true;
        return $this;
    }

    public function isEnabledViewSwitcher()
    {
        return $this->_enableViewSwitcher;
    }

    public function disableExpanded()
    {
        $this->_isExpanded = false;
        return $this;
    }

    public function enableExpanded()
    {
        $this->_isExpanded = true;
        return $this;
    }

    public function isExpanded()
    {
        return $this->_isExpanded;
    }

    public function getDefaultPerPageValue()
    {
        if ($this->getCurrentMode() == 'list') {
            return Mage::getStoreConfig('catalog/frontend/list_per_page');
        }
        elseif ($this->getCurrentMode() == 'grid') {
            return Mage::getStoreConfig('catalog/frontend/grid_per_page');
        }
        return 0;
    }

    public function getAvailableLimit()
    {
        if ($this->getCurrentMode() == 'list') {
            $perPageValues = (string) Mage::getConfig()->getNode('frontend/catalog/per_page_values/list');
            $perPageValues = explode(',', $perPageValues);
            $perPageValues = array_combine($perPageValues, $perPageValues);
            return ($perPageValues + array('all'=>Mage::helper('catalog')->__('All')));
        }
        elseif ($this->getCurrentMode() == 'grid') {
            $perPageValues = (string) Mage::getConfig()->getNode('frontend/catalog/per_page_values/grid');
            $perPageValues = explode(',', $perPageValues);
            $perPageValues = array_combine($perPageValues, $perPageValues);
            return ($perPageValues + array('all'=>Mage::helper('catalog')->__('All')));
        }
        return parent::getAvailableLimit();
    }

    public function getLimit()
    {
        $limits = $this->getAvailableLimit();
        if ($limit = $this->getRequest()->getParam($this->getLimitVarName())) {
            if (isset($limits[$limit])) {
                return $limit;
            }
        }
        if ($limit = $this->getDefaultPerPageValue()) {
            if (isset($limits[$limit])) {
                return $limit;
            }
        }

        $limits = array_keys($limits);
        return $limits[0];
    }
}
