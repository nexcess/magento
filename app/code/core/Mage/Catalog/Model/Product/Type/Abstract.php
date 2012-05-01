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
 * Abstract model for product type implementation
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
abstract class Mage_Catalog_Model_Product_Type_Abstract
{
    protected $_product;
    protected $_typeId;
    protected $_setAttributes;
    protected $_editableAttributes;

    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    public function setTypeId($typeId)
    {
        $this->_typeId = $typeId;
        return $this;
    }

    /**
     * Retrieve catalog product object
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return $this->_product;
    }

    public function getSetAttributes()
    {
        if (is_null($this->_setAttributes)) {
            $attributes = $this->getProduct()->getResource()
                ->loadAllAttributes($this->getProduct())
                ->getAttributesByCode();
            $this->_setAttributes = array();
            foreach ($attributes as $attribute) {
                if ($attribute->getAttributeSetId() == $this->getProduct()->getAttributeSetId()) {
                    $attribute->setDataObject($this->getProduct());
                    $this->_setAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
        return $this->_setAttributes;
    }

    /**
     * Retrieve product type attributes
     *
     * @return array
     */
    public function getEditableAttributes()
    {
        if (is_null($this->_editableAttributes)) {
            $this->_editableAttributes = array();
            foreach ($this->getSetAttributes() as $attributeCode => $attribute) {
                if (!is_array($attribute->getApplyTo())
                    || count($attribute->getApplyTo())==0
                    || in_array($this->getProduct()->getTypeId(), $attribute->getApplyTo())) {
                    $this->_editableAttributes[$attributeCode] = $attribute;
                }
            }
        }
        return $this->_editableAttributes;
    }

    /**
     * Retrieve product attribute by identifier
     *
     * @param   int $attributeId
     * @return  Mage_Eav_Model_Entity_Attribute_Abstract
     */
    public function getAttributeById($attributeId)
    {
        foreach ($this->getSetAttributes() as $attribute) {
        	if ($attribute->getId() == $attributeId) {
        	    return $attribute;
        	}
        }
        return null;
    }

    /**
     * Check is product available for sale
     *
     * @return bool
     */
    public function isSalable()
    {
        $salable = $this->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        if ($salable && $this->getProduct()->hasData('is_salable')) {
            return $this->getProduct()->getData('is_salable');
        }
        return $salable;
    }

    /**
     * Save type related data
     *
     * @return unknown
     */
    public function save()
    {
        return $this;
    }
}