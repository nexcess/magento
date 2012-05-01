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
 * Catalog products compare block
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
 class Mage_Catalog_Block_Product_Compare_List extends Mage_Core_Block_Template
 {
    protected $_items = null;
    protected $_attributes = null;

    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle(Mage::helper('catalog')->__('Compare Products List') . ' - ' . $headBlock->getDefaultTitle());
        }
        return parent::_prepareLayout();
    }

    public function getItems()
    {
        if(is_null($this->_items)) {
            $this->_items = Mage::getResourceModel('catalog/product_compare_item_collection')
                ->useProductItem(true)
                ->setStoreId(Mage::app()->getStore()->getId());

            if(Mage::getSingleton('customer/session')->isLoggedIn()) {
                $this->_items->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());
            } else {
                $this->_items->setVisitorId(Mage::getSingleton('log/visitor')->getId());
            }

            $this->_items
                ->loadComaparableAttributes()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('small_image')
                ->addAttributeToSelect('tax_class_id')
                ->useProductItem();
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($this->_items);
        }

        return $this->_items;
    }

    public function getAttributes()
    {
        if(is_null($this->_attributes)) {
            $this->_setAttributesFromProducts();
        }

        return $this->_attributes;
    }

    protected function _setAttributesFromProducts()
    {
        $this->_attributes = array();
        foreach($this->getItems() as $item) {
            foreach ($item->getAttributes() as $attribute) {
                if($attribute->getIsComparable() && !$this->hasAttribute($attribute->getAttributeCode()) && $item->getData($attribute->getAttributeCode())!==null) {
                    $this->_attributes[] = $attribute;
                }
            }
        }

        return $this;
    }

    public function hasAttribute($code)
    {
        foreach($this->_attributes as $attribute) {
            if($attribute->getAttributeCode()==$code) {
                return true;
            }
        }

        return false;
    }

    public function getProductAttributeValue($product, $attribute)
    {
        if(!$product->hasData($attribute->getAttributeCode())) {
            return '&nbsp;';
        }

        if($attribute->getSourceModel() || in_array($attribute->getFrontendInput(), array('select','boolean','multiselect'))){

            //$value = $attribute->getSource()->getOptionText($product->getData($attribute->getAttributeCode()));
            $value = $attribute->getFrontend()->getValue($product);
        } else {
            $value = $product->getData($attribute->getAttributeCode());
        }
        return $value ? $value : '&nbsp';
    }

    public function getPrintUrl()
    {
        return $this->getUrl('*/*/*', array('_current'=>true, 'print'=>1));
    }

 }
