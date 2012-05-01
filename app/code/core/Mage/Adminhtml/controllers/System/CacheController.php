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
 * config controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_System_CacheController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $this->_setActiveMenu('system/cache');

        $this->_addContent($this->getLayout()->createBlock('adminhtml/system_cache_edit')->initForm());

        $this->renderLayout();
    }

    public function saveAction()
    {
        $allCache = $this->getRequest()->getPost('all_cache');
        if ($allCache=='disable' || $allCache=='refresh') {
            Mage::app()->cleanCache();
        }

        $e = $this->getRequest()->getPost('enable');
        $enable = array();
        $clean = array();
        foreach (Mage::helper('core')->getCacheTypes() as $type=>$label) {
            $flag = $allCache!='disable' && (!empty($e[$type]) || $allCache=='enable');
            $enable[$type] = $flag ? 1 : 0;
            if ($allCache=='' && !$flag) {
                $clean[] = $type;
            }
        }
        if (!empty($clean)) {
            Mage::app()->cleanCache($clean);
        }

        Mage::app()->saveCache(serialize($enable), 'use_cache', array(), null);

        if ($this->getRequest()->getPost('refresh_catalog_rewrites')) {
            Mage::getSingleton('catalog/url')->refreshRewrites();
        }

        if( $this->getRequest()->getPost('clear_images_cache') ) {
            Mage::getModel('catalog/product_image')->clearCache();
        }

        #Mage::getSingleton('core/resource')->setAutoUpdate($this->getRequest()->getPost('db_auto_update'));

        $this->_redirect('*/*');
    }

    protected function _isAllowed()
    {
	    return Mage::getSingleton('admin/session')->isAllowed('system/cache');
    }
}
