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
 * Catalog layered navigation view block
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Block_Layer_View extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/layer/view.phtml');
    }

    public function _prepareLayout()
    {
        $this->setChild('layer_state',
            $this->getLayout()->createBlock('catalog/layer_state'));

        $this->setChild('category_filter',
            $this->getLayout()->createBlock('catalog/layer_filter_category')->init());






        /* --- !!! TO BE REMOVED!!! --- */
        /*
        $this->setChild('price_filterold',
            $this->getLayout()->createBlock('catalog/layer_filter_priceold')->init());

        $filterableAttributes = $this->_getFilterableAttributes();
        foreach ($filterableAttributes as $attribute) {
            $this->setChild($attribute->getAttributeCode().'_filterold',
                $this->getLayout()->createBlock('catalog/layer_filter_attributeold')
                    ->setAttributeModel($attribute)
                    ->init());
        }
        */
        /* --- !!! TO BE REMOVED!!! --- */





/*
        $this->setChild('_price_filter',
            $this->getLayout()->createBlock('catalog/layer_filter_price')->init());
*/
        $filterableAttributes = $this->_getFilterableAttributes();
        foreach ($filterableAttributes as $attribute) {
            $filterBlockName = 'catalog/layer_filter_attribute';
            if ($attribute->getFrontendInput() == 'price')
                $filterBlockName = 'catalog/layer_filter_price';

            $this->setChild($attribute->getAttributeCode().'_filter',
                $this->getLayout()->createBlock($filterBlockName)
                    ->setAttributeModel($attribute)
                    ->init());
        }


        return parent::_prepareLayout();
    }

    public function getStateHtml()
    {
        return $this->getChildHtml('layer_state');
    }

    /**
     * Retrieve filters
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = array();
        if ($categoryFilter = $this->_getCategoryFilter()) {
            $filters[] = $categoryFilter;
        }






        /* --- !!! TO BE REMOVED!!! --- */
        /*
        $filters[] = $this->getChild('price_filterold');

        $filterableAttributes = $this->_getFilterableAttributes();
        foreach ($filterableAttributes as $attribute) {
            $filters[] = $this->getChild($attribute->getAttributeCode().'_filterold');
        }
        */
        /* --- !!! TO BE REMOVED!!! --- */







        if ($priceFilter = $this->_getPriceFilter()) {
            $filters[] = $priceFilter;
        }

        $filterableAttributes = $this->_getFilterableAttributes();
        foreach ($filterableAttributes as $attribute) {
            $filters[] = $this->getChild($attribute->getAttributeCode().'_filter');
        }

        return $filters;
    }

    protected function _getCategoryFilter()
    {
        return $this->getChild('category_filter');
    }

    protected function _getPriceFilter()
    {
        return $this->getChild('_price_filter');
    }

    protected function _getFilterableAttributes()
    {
        $attributes = $this->getData('_filterable_attributes');
        if (is_null($attributes)) {
            $attributes = Mage::getSingleton('catalog/layer')->getFilterableAttributes();
            $this->setData('_filterable_attributes', $attributes);
        }
        return $attributes;
    }

    public function canShowOptions()
    {
        foreach ($this->getFilters() as $filter) {
            if ($filter->getItemsCount()) {
                return true;
            }
        }
        return false;
    }

    public function canShowBlock()
    {
        return $this->canShowOptions() || count(Mage::getSingleton('catalog/layer')->getState()->getFilters());
    }
}
