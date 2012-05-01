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
 * Tabs block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Widget_Tabs extends Mage_Adminhtml_Block_Widget
{
    /**
     * tabs structure
     *
     * @var array
     */
    protected $_tabs = array();

    /**
     * Active tab key
     *
     * @var string
     */
    protected $_activeTab = null;

    /**
     * Destination HTML element id
     *
     * @var string
     */
    protected $_destElementId = 'content';

    protected function _construct()
    {
        $this->setTemplate('widget/tabs.phtml');
    }

    /**
     * retrieve destination html element id
     *
     * @return string
     */
    public function getDestElementId()
    {
        return $this->_destElementId;
    }

    public function setDestElementId($elementId)
    {
        $this->_destElementId = $elementId;
        return $this;
    }

    /**
     * Add new tab
     *
     * @param   string $tabId
     * @param   array|Varien_Object $tab
     * @return  Mage_Adminhtml_Block_Widget_Tabs
     */
    public function addTab($tabId, $tab)
    {
        if (is_array($tab)) {
            $this->_tabs[$tabId] = new Varien_Object($tab);
        }
        elseif ($tab instanceof Varien_Object) {
        	$this->_tabs[$tabId] = $tab;
        }
        elseif (is_string($tab)) {
            if (strpos($tab, '/')) {
                $this->_tabs[$tabId] = $this->getLayout()->createBlock($tab);
            }
            elseif ($this->getChild($tab)) {
                $this->_tabs[$tabId] = $this->getChild($tab);
            }
            else {
                $this->_tabs[$tabId] = null;
            }

            if (!($this->_tabs[$tabId] instanceof Mage_Adminhtml_Block_Widget_Tab_Interface)) {
                throw new Exception(Mage::helper('adminhtml')->__('Wrong tab configuration'));
            }
            $this->_tabs[$tabId]->setTabId($tabId);

            if (is_null($this->_activeTab)) $this->_activeTab = $tabId;
            return $this;
        }
        else {
            throw new Exception(Mage::helper('adminhtml')->__('Wrong tab configuration'));
        }

        if (is_null($this->_tabs[$tabId]->getUrl())) {
            $this->_tabs[$tabId]->setUrl('#');
        }

        if (!$this->_tabs[$tabId]->getTitle()) {
            $this->_tabs[$tabId]->setTitle($this->_tabs[$tabId]->getLabel());
        }

        $this->_tabs[$tabId]->setId($tabId);

        if (is_null($this->_activeTab)) $this->_activeTab = $tabId;
        if (true === $this->_tabs[$tabId]->getActive()) $this->setActiveTab($tabId);

        return $this;
    }

    public function getActiveTabId()
    {
        return $this->getTabId($this->_tabs[$this->_activeTab]);
    }

    /**
     * Set Active Tab
     *
     * @param string $tabId
     * @return Mage_Adminhtml_Block_Widget_Tabs
     */
    public function setActiveTab($tabId)
    {
        if (isset($this->_tabs[$tabId])) {
            $this->_activeTab = $tabId;
            if (!(is_null($this->_activeTab)) && ($tabId !== $this->_activeTab)) {
                foreach ($this->_tabs as $id => $tab) {
                    $tab->setActive($id === $tabId);
                }
            }
        }
        return $this;
    }

    protected function _beforeToHtml()
    {
        if ($activeTab = $this->getRequest()->getParam('active_tab')) {
            $this->setActiveTab($activeTab);
        }
        $this->assign('tabs', $this->_tabs);
        return parent::_beforeToHtml();
    }

    public function getJsObjectName()
    {
        return $this->getId() . 'JsTabs';
    }

    public function getTabsIds()
    {
        if (empty($this->_tabs))
            return array();
        return array_keys($this->_tabs);
    }

    public function getTabId($tab, $withPrefix = true)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            return ($withPrefix ? $this->getId().'_' : '').$tab->getTabId();
        }
        return ($withPrefix ? $this->getId().'_' : '').$tab->getId();
    }

    public function canShowTab($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            return $tab->canShowTab();
        }
        return true;
    }

    public function getTabIsHidden($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            return $tab->isHidden();
        }
        return $tab->getIsHidden();
    }

    public function getTabUrl($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            if (method_exists($tab, 'getTabUrl')) {
                return $tab->getTabUrl();
            }
            return '#';
        }
        if (!is_null($tab->getUrl())) {
            return $tab->getUrl();
        }
        return '#';
    }

    public function getTabTitle($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            return $tab->getTabTitle();
        }
        return $tab->getTitle();
    }

    public function getTabClass($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            if (method_exists($tab, 'getTabClass')) {
                return $tab->getTabClass();
            }
            return '';
        }
        return $tab->getClass();
    }


    public function getTabLabel($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            return $tab->getTabLabel();
        }
        return $tab->getLabel();
    }

    public function getTabContent($tab)
    {
        if ($tab instanceof Mage_Adminhtml_Block_Widget_Tab_Interface) {
            return $tab->toHtml();
        }
        return $tab->getContent();
    }
}
