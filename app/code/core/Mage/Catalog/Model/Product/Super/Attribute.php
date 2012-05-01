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
 * Catalog super product attribute model
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Product_Super_Attribute extends Mage_Core_Model_Abstract
{

    protected $_pricingCollection = null;

    protected function _construct()
    {
        $this->_init('catalog/product_super_attribute');
    }

    public function getDataForSave()
    {
        return $this->toArray(array('product_id','attribute_id','position'));
    }

    public function getPricingCollection()
    {
        if(is_null($this->_pricingCollection)) {
            $this->_pricingCollection = $this->_getResource()->getPricingCollection($this);
        }

        return $this->_pricingCollection;
    }

    public function getValues(Mage_Eav_Model_Entity_Attribute_Abstract $attribute=null)
    {
        if($this->getData('values')) {
            return $this->getData('values');
        }

        if(!is_null($attribute)) {
            $this->getPricingCollection()->walk('setPricingLabelFromAttribute', array($attribute));
        }
        $collectionToArray = $this->getPricingCollection()->toArray(array('value_id', 'value_index', 'label', 'is_percent', 'pricing_value'));
        return $collectionToArray['items'];
    }

}
