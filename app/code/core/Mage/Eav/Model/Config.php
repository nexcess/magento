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
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Eav_Model_Config
{
    /**
     * Array data loaded from cache
     *
     * @var array
     */
    protected $_data;

    protected $_objects;
    protected $_references;

    protected function _load($id)
    {
        if (isset($this->_references[$id])) {
            $id = $this->_references[$id];
        }
        return isset($this->_objects[$id]) ? $this->_objects[$id] : null;
    }

    protected function _save($obj, $id)
    {
        $this->_objects[$id] = $obj;
        return $this;
    }

    protected function _reference($ref, $id)
    {
        $this->_references[$ref] = $id;
        return $this;
    }

    protected function _initEntityTypes()
    {
        if (isset($this->_data)) {
            return;
        }
        $useCache = Mage::app()->useCache('eav');
        if ($useCache && $cache = Mage::app()->loadCache('EAV_ENTITY_TYPE_CODES')) {
            $this->_data['entity_type_codes'] = unserialize($cache);
            return;
        }
        $entityTypes = Mage::getModel('eav/entity_type')->getCollection();
        $codes = array();
        foreach ($entityTypes as $id=>$t) {
            if (!$t->getAttributeModel()) {
                $t->setAttributeModel('eav/entity_attribute');
            }
            $code = $t->getEntityTypeCode();
            $this->_save($t, 'EAV_ENTITY_TYPE/'.$code);
            $this->_reference('EAV_ENTITY_TYPE/'.$id, 'EAV_ENTITY_TYPE/'.$code);
            $codes[$id] = $code;
            if ($useCache) {
                Mage::app()->saveCache(serialize($t->getData()), 'EAV_ENTITY_TYPE_'.$code,
                    array('eav', Mage_Eav_Model_Entity_Attribute::CACHE_TAG)
                );
            }
        }
        $this->_data['entity_type_codes'] = $codes;
        if ($useCache) {
            Mage::app()->saveCache(serialize($this->_data['entity_type_codes']), 'EAV_ENTITY_TYPE_CODES',
                array('eav', Mage_Eav_Model_Entity_Attribute::CACHE_TAG)
            );
        }
    }

    public function getEntityType($code)
    {
        if ($code instanceof Mage_Eav_Model_Entity_Type) {
            return $code;
        }
        //Varien_Profiler::start('TEST: '.__METHOD__);
        $this->_initEntityTypes();
        if ($entityType = $this->_load('EAV_ENTITY_TYPE/'.$code)) {
            //Varien_Profiler::stop('TEST: '.__METHOD__);
            return $entityType;
        }
        if (is_numeric($code)) {
            if (isset($this->_data['entity_type_codes'][$code])) {
                $code = $this->_data['entity_type_codes'][$code];
            } else {
                throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('Invalid entity_type specified: %s', $code));
            }
        }
        if (!$cache = Mage::app()->loadCache('EAV_ENTITY_TYPE_'.$code)) {
            throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('Invalid entity_type specified: %s', $code));
        }
        $data = unserialize($cache);
        //()
//        if (isset($data['attributes'])) {
//            $this->_data['attributes'][$code] = $data['attributes'];
//            foreach ($data['attributes'] as $attr) {
//                $attribute = Mage::getModel($attr['attribute_model'], $attr);
//                Mage::objects()->save($attribute, 'EAV_ATTRIBUTE/'.$code.'/'.$attr['attribute_code']);
//            }
//            unset($data['attributes']);
//        }
        //()
        $entityType = Mage::getModel('eav/entity_type', $data);
        $this->_save($entityType, 'EAV_ENTITY_TYPE/'.$code);
        Varien_Profiler::stop('TEST: '.__METHOD__);

        return $entityType;
    }

    protected function _initAttributes($entityType)
    {
        $entityType = $this->getEntityType($entityType);
        $entityTypeCode = $entityType->getEntityTypeCode();

        if (isset($this->_data['attributes'][$entityTypeCode]) || $entityType->getAttributeCodes()) {
            return;
        }

        $useCache = Mage::app()->useCache('eav');

        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($entityType->getId());

        $defaultAttributeModel = $entityType->getAttributeModel();
        $codes = array();
        $attributesData = array();
        foreach ($attributes as $a) {
            if (!$a->getAttributeModel()) {
                $a->setAttributeModel($defaultAttributeModel);
            }
            if ($a->getAttributeModel()!=='eav/entity_attribute') {
                $a = Mage::getModel($a->getAttributeModel(), $a->getData());
            }
            $code = $a->getAttributeCode();
            $this->_save($a, 'EAV_ATTRIBUTE/'.$entityTypeCode.'/'.$code);
            $this->_reference($a->getId(), $code);
            $codes[$a->getId()] = $code;
            if ($useCache) {
//                $attributesData[] = $a->getData(); //()
                Mage::app()->saveCache(serialize($a->getData()), 'EAV_ATTRIBUTE_'.$entityTypeCode.'__'.$code,
                    array('eav', Mage_Eav_Model_Entity_Attribute::CACHE_TAG)
                );
            }
        }

        $entityType->setAttributeCodes($codes);
        if ($useCache) {
            $data = $entityType->getData();
//            $data['attributes'] = $attributesData; //()
            Mage::app()->saveCache(serialize($data), 'EAV_ENTITY_TYPE_'.$entityTypeCode,
                array('eav', Mage_Eav_Model_Entity_Attribute::CACHE_TAG)
            );
        }
    }

    public function getAttribute($entityType, $code)
    {
        if ($code instanceof Mage_Eav_Model_Entity_Attribute_Interface) {
            return $code;
        }
        Varien_Profiler::start('TEST: '.__METHOD__);
        $this->_initAttributes($entityType);
        $entityTypeCode = $this->getEntityType($entityType)->getEntityTypeCode();
        $entityType = $this->getEntityType($entityType);
        $attrCodes = $entityType->getAttributeCodes();
        if (is_numeric($code)) {
            if (isset($attrCodes[$code])) {
                $code = $attrCodes[$code];
            } else {
                /**
                 * Problems with existing data for invalid attribute ids
                 */
                //throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('Invalid attribute specified: %s', $code));
                return false;
            }
        }
        if ($attribute = $this->_load('EAV_ATTRIBUTE/'.$entityTypeCode.'/'.$code)) {
            Varien_Profiler::stop('TEST: '.__METHOD__);
            return $attribute;
        }

        // ()
//        if (isset($this->_data['attributes'][$entityTypeCode][$code])) {
//            $data = $this->_data['attributes'][$entityTypeCode][$code];
//            unset($this->_data['attributes'][$entityTypeCode][$code]);
//        } else
        // ()
        if (in_array($code, $attrCodes)) {
            if ($cache = Mage::app()->loadCache('EAV_ATTRIBUTE_'.$entityTypeCode.'__'.$code)) {
                $data = unserialize($cache);
            }
        }
        if (empty($data)) {
            Varien_Profiler::stop('TEST: '.__METHOD__);
            return false;
//            $data = array(
//                'attribute_code'=>$code,
//                'attribute_model'=>'eav/entity_attribute',
//                'backend_type'=>'static',
//            );
        }

        $attribute = Mage::getModel($data['attribute_model'], $data);
        $attribute->setEntityType($entityType);

        $this->_save($attribute, 'EAV_ATTRIBUTE/'.$entityTypeCode.'/'.$code);
        Varien_Profiler::stop('TEST: '.__METHOD__);

        return $attribute;
    }

    public function getEntityAttributeCodes($entityType)
    {
        $this->_initAttributes($entityType);
        return $this->getEntityType($entityType)->getAttributeCodes();
    }

    public function clear()
    {
        $this->_data    = null;
        $this->_objects = null;
        $this->_references = null;
        return $this;
    }
}