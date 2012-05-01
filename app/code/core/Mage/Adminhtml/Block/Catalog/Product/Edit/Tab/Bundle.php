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
 * Adminhtml catalog product bundle option block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
 class Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Bundle extends Mage_Adminhtml_Block_Widget
 {

    protected  $_bundleOptionCollection = null;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/product/edit/bundle/options.phtml');
        $this->setId('bundle_options');
    }

    public function getJsObjectName()
    {
        return uc_words($this->getId(), '').'JsObject';
    }

    public function getTabJsObjectName()
    {
        return uc_words($this->getId(), '').'TabJsObject';
    }

    public function getJsTemplateHtmlId()
    {
        return $this->getId().'_option_new_template';
    }

    public function getJsContainerHtmlId()
    {
        return $this->getId().'_option_container';
    }

    protected function _prepareLayout()
    {
        $this->setChild('option_form',
            $this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_bundle_option')
                ->setParent($this)
        );

        return parent::_prepareLayout();
    }

    public function getBundleOptions()
    {
        if(is_null($this->_bundleOptionCollection)) {
            $this->_bundleOptionCollection = Mage::registry('product')->getBundleOptionCollection()
                ->setOrder('position', 'asc')
                ->load();
        }

        return $this->_bundleOptionCollection;
    }

    public function getOptionProductsJSON($option)
    {
        $data = $option->getLinkCollection()->toArray();

        if(sizeof($data)==0) {
            return '{}';
        }
        return Zend_Json_Encoder::encode($data);
    }

    public function getEscaped($value)
    {
        return addcslashes($value, "\\'\n\r");
    }

}
