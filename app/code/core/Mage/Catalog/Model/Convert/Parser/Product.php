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


class Mage_Catalog_Model_Convert_Parser_Product
    extends Mage_Eav_Model_Convert_Parser_Abstract
{
    const MULTI_DELIMITER = ' , ';
    protected $_resource;

    /**
     * Product collections per store
     *
     * @var array
     */
    protected $_collections;

    protected $_productTypes = array(
        'simple'=>'Simple',
        'bundle'=>'Bundle',
        'configurable'=>'Configurable',
        'grouped'=>'Grouped',
        'virtual'=>'Virtual',
    );

    protected $_inventoryFields = array(
        'qty', 'min_qty', 'use_config_min_qty',
        'is_qty_decimal', 'backorders', 'use_config_backorders',
        'min_sale_qty','use_config_min_sale_qty','max_sale_qty',
        'use_config_max_sale_qty','is_in_stock','notify_stock_qty','use_config_notify_stock_qty'
    );

    protected $_imageFields = array(
        'image', 'small_image', 'thumbnail'
    );

    protected $_inventoryItems = array();

    protected $_productModel;

    protected $_setInstances = array();

    protected $_store;
    protected $_storeId;
    protected $_attributes = array();

    /**
     * @return Mage_Catalog_Model_Mysql4_Convert
     */
    public function getResource()
    {
        if (!$this->_resource) {
            $this->_resource = Mage::getResourceSingleton('catalog_entity/convert');
                #->loadStores()
                #->loadProducts()
                #->loadAttributeSets()
                #->loadAttributeOptions();
        }
        return $this->_resource;
    }

    public function getCollection($storeId)
    {
        if (!isset($this->_collections[$storeId])) {
            $this->_collections[$storeId] = Mage::getResourceModel('catalog/product_collection');
            $this->_collections[$storeId]->getEntity()->setStore($storeId);
        }
        return $this->_collections[$storeId];
    }

    public function getProductTypeName($id)
    {
        return isset($this->_productTypes[$id]) ? $this->_productTypes[$id] : false;
    }

    public function getProductTypeId($name)
    {
        return array_search($name, $this->_productTypes);
    }

    /**
     * Retrieve product model cache
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProductModel()
    {
        if (is_null($this->_productModel)) {
            $productModel = Mage::getModel('catalog/product');
            $this->_productModel = Varien_Object_Cache::singleton()->save($productModel);
        }
        return Varien_Object_Cache::singleton()->load($this->_productModel);
    }

    /**
     * Retrieve current store model
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        if (is_null($this->_store)) {
            try {
                $store = Mage::app()->getStore($this->getVar('store'));
            }
            catch (Exception $e) {
                $this->addException(Mage::helper('catalog')->__('Invalid store specified'), Varien_Convert_Exception::FATAL);
                throw $e;
            }
            $this->_store = $store;
        }
        return $this->_store;
    }

    /**
     * Retrieve store ID
     *
     * @return int
     */
    public function getStoreId()
    {
        if (is_null($this->_storeId)) {
            $this->_storeId = $this->getStore()->getId();
        }
        return $this->_storeId;
    }

    public function getAttributeSetInstance()
    {
        $productType = $this->getProductModel()->getType();
        $attributeSetId = $this->getProductModel()->getAttributeSetId();

        if (!isset($this->_setInstances[$productType][$attributeSetId])) {
            $this->_setInstances[$productType][$attributeSetId] =
                Mage::getSingleton('catalog/product_type')->factory($this->getProductModel());
        }

        return $this->_setInstances[$productType][$attributeSetId];
    }

    /**
     * Retrieve eav entity attribute model
     *
     * @param string $code
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getAttribute($code)
    {
        if (!isset($this->_attributes[$code])) {
            $this->_attributes[$code] = $this->getProductModel()->getResource()->getAttribute($code);
        }
        return $this->_attributes[$code];
    }

    public function parse()
    {
        $data = $this->getData();

        $entityTypeId = Mage::getSingleton('eav/config')->getEntityType('catalog_product')->getId();

        $result = array();
        $inventoryFields = array();
        foreach ($data as $i=>$row) {
            $this->setPosition('Line: '.($i+1));
            try {
                // validate SKU
                if (empty($row['sku'])) {
                    $this->addException(Mage::helper('catalog')->__('Missing SKU, skipping the record'), Mage_Dataflow_Model_Convert_Exception::ERROR);
                    continue;
                }
                $this->setPosition('Line: '.($i+1).', SKU: '.$row['sku']);

                // try to get entity_id by sku if not set
                if (empty($row['entity_id'])) {
                    $row['entity_id'] = $this->getResource()->getProductIdBySku($row['sku']);
                }

                // if attribute_set not set use default
                if (empty($row['attribute_set'])) {
                    $row['attribute_set'] = 'Default';
                }
                // get attribute_set_id, if not throw error
                $row['attribute_set_id'] = $this->getAttributeSetId($entityTypeId, $row['attribute_set']);
                if (!$row['attribute_set_id']) {
                    $this->addException(Mage::helper('catalog')->__("Invalid attribute set specified, skipping the record"), Mage_Dataflow_Model_Convert_Exception::ERROR);
                    continue;
                }

                if (empty($row['type'])) {
                    $row['type'] = 'Simple';
                }
                // get product type_id, if not throw error
                $row['type_id'] = $this->getProductTypeId($row['type']);
                if (!$row['type_id']) {
                    $this->addException(Mage::helper('catalog')->__("Invalid product type specified, skipping the record"), Mage_Dataflow_Model_Convert_Exception::ERROR);
                    continue;
                }

                // get store ids
                $storeIds = $this->getStoreIds(isset($row['store']) ? $row['store'] : $this->getVar('store'));
                if (!$storeIds) {
                    $this->addException(Mage::helper('catalog')->__("Invalid store specified, skipping the record"), Mage_Dataflow_Model_Convert_Exception::ERROR);
                    continue;
                }

                // import data
                $rowError = false;
                foreach ($storeIds as $storeId) {
                    $collection = $this->getCollection($storeId);
                    $entity = $collection->getEntity();

                    $model = Mage::getModel('catalog/product');
                    $model->setStoreId($storeId);
                    if (!empty($row['entity_id'])) {
                        $model->load($row['entity_id']);
                    }
                    foreach ($row as $field=>$value) {
                        $attribute = $entity->getAttribute($field);

                        if (!$attribute) {
                            //$inventoryFields[$row['sku']][$field] = $value;

                            if (in_array($field, $this->_inventoryFields)) {
                                $inventoryFields[$row['sku']][$field] = $value;
                            }
                            continue;
                            #$this->addException(Mage::helper('catalog')->__("Unknown attribute: %s", $field), Mage_Dataflow_Model_Convert_Exception::ERROR);
                        }
                        if ($attribute->usesSource()) {
                            $source = $attribute->getSource();
                            $optionId = $this->getSourceOptionId($source, $value);
                            if (is_null($optionId)) {
                                $rowError = true;
                                $this->addException(Mage::helper('catalog')->__("Invalid attribute option specified for attribute %s (%s), skipping the record", $field, $value), Mage_Dataflow_Model_Convert_Exception::ERROR);
                                continue;
                            }
                            $value = $optionId;
                        }
                        $model->setData($field, $value);

                    }//foreach ($row as $field=>$value)

                    //echo 'Before **********************<br/><pre>';
                    //print_r($model->getData());
                    if (!$rowError) {
                        $collection->addItem($model);
                    }
                    unset($model);
                } //foreach ($storeIds as $storeId)
            } catch (Exception $e) {
                if (!$e instanceof Mage_Dataflow_Model_Convert_Exception) {
                    $this->addException(Mage::helper('catalog')->__("Error during retrieval of option value: %s", $e->getMessage()), Mage_Dataflow_Model_Convert_Exception::FATAL);
                }
            }
        }

        // set importinted to adaptor
        if (sizeof($inventoryFields) > 0) {
            Mage::register('current_imported_inventory', $inventoryFields);
            //$this->setInventoryItems($inventoryFields);
        } // end setting imported to adaptor

        $this->setData($this->_collections);
        return $this;
    }

    public function setInventoryItems($items)
    {
        $this->_inventoryItems = $items;
    }

    public function getInventoryItems()
    {
        return $this->_inventoryItems;
    }


    public function unparse()
    {
        $systemFields = array(
            'entity_id',
            'entity_type_id',
            'attribute_set_id',
            'type_id',
            'created_at',
            'updated_at',
            'item_id',
            'product_id',
            'stock_id',
        );

        $entityIds = $this->getData();

        foreach ($entityIds as $i => $entityId) {

//            print memory_get_usage() . "<br />";

            $product = $this->getProductModel()
                ->setData(array())
                ->setStoreId($this->getStoreId())
                ->load($entityId);
            $product->setTypeInstance($this->getAttributeSetInstance());
            /* @var $product Mage_Catalog_Model_Product */

            $position = Mage::helper('catalog')->__('Line %d, SKU: %s', ($i+1), $product->getSku());
            $this->setPosition($position);

            $row = array(
                'store'         => $this->getStore()->getCode(),
                'attribute_set' => $this->getAttributeSetName($product->getEntityTypeId(), $product->getAttributeSetId()),
                'type'          => $this->getProductTypeName($product->getTypeId()),
            );

            foreach ($product->getData() as $field => $value) {
                if (in_array($field, $systemFields) || is_object($value)) {
                    continue;
                }

                $attribute = $this->getAttribute($field);
                if (!$attribute) {
                    continue;
                }

                if ($attribute->usesSource()) {
                    $option = $attribute->getSource()->getOptionText($value);
                    if ($value && empty($option)) {
                        $message = Mage::helper('catalog')->__("Invalid option id specified for %s (%s), skipping the record", $field, $value);
                        $this->addException($message, Mage_Dataflow_Model_Convert_Exception::ERROR);
                        continue;
                    }
                    if (is_array($option)) {
                        $value = join(self::MULTI_DELIMITER, $option);
                    } else {
                        $value = $option;
                    }
                    unset($option);
                }
                elseif (is_array($value)) {
                    continue;
                }

                $row[$field] = $value;
            }

            if ($stockItem = $product->getStockItem()) {
                foreach ($stockItem->getData() as $field => $value) {
                    if (in_array($field, $systemFields) || is_object($value)) {
                        continue;
                    }
                    $row[$field] = $value;
                }
            }

            foreach ($this->_imageFields as $field) {
                if (isset($row[$field]) && $row[$field] == 'no_selection') {
                    $row[$field] = null;
                }
            }

            $batchExport = $this->getBatchExportModel()
                ->setId(null)
                ->setBatchId($this->getBatchModel()->getId())
                ->setBatchData($row)
                ->setStatus(1)
                ->save();
        }

        //print '<pre>' . memory_get_usage() .' byte(s)</pre>';

//        $collections = $this->getData();
//        if ($collections instanceof Mage_Eav_Model_Entity_Collection_Abstract) {
//            $collections = array($collections->getStoreId()=>$collections);
//        } elseif (!is_array($collections)) {
//            $this->addException(Mage::helper('catalog')->__("Array of Entity collections is expected"), Mage_Dataflow_Model_Convert_Exception::FATAL);
//        }
//
//        $stockItem = Mage::getModel('cataloginventory/stock_item');



//        foreach ($collections as $storeId=>$collection) {
//            if (!$collection instanceof Mage_Eav_Model_Entity_Collection_Abstract) {
//                $this->addException(Mage::helper('catalog')->__("Entity collection is expected"), Mage_Dataflow_Model_Convert_Exception::FATAL);
//            }
//
//            $data = array();
//            foreach ($collection->getIterator() as $i=>$model) {
//                /**
//                 * @var $model Mage_Catalog_Model_Product
//                 */
//                $productId = $model->getId();
//                $this->setPosition('Line: '.($i+1).', SKU: '.$model->getSku());
//
//                $row = array(
//                    'store'=>$this->getStoreCode($this->getVar('store') ? $this->getVar('store') : $storeId),
//                    'attribute_set'=>$this->getAttributeSetName($model->getEntityTypeId(), $model->getAttributeSetId()),
//                    'type'=>$this->getProductTypeName($model->getTypeId()),
//                );
//
//                foreach ($model->getData() as $field=>$value) {
//                    if (in_array($field, $systemFields)) {
//                        continue;
//                    }
//                    $attribute = $model->getResource()->getAttribute($field);
//                    if (!$attribute) {
//                        continue;
//                    }
//
//                    if ($attribute->usesSource()) {
//                        $option = $attribute->getSource()->getOptionText($value);
//                        if ($value && empty($option)) {
//                            $this->addException(Mage::helper('catalog')->__("Invalid option id specified for %s (%s), skipping the record", $field, $value), Mage_Dataflow_Model_Convert_Exception::ERROR);
//                            continue;
//                        }
//                        if (is_array($option)) {
//                            $value = join(',', $option);
//                        } else {
//                            $value = $option;
//                        }
//                    }
//                    $row[$field] = $value;
//                }
//
//                $stockItem->unsetData();
//                if ($stockItem) {
//                    $stockItem->loadByProduct($productId);
//                    if ($stockItem->getId()) foreach ($stockItem->getData() as $field=>$value) {
//                        if (in_array($field, $this->_inventoryFields)) {
//                            $row[$field] = $value;
//                        }
//                    }
//                }
//
//                $data[] = $row;
//                //var_dump($row);
//            }
//        }

//        $this->setData($data);
        return $this;
    }

    public function getExternalAttributes()
    {
        $internal = array(
            'entity_id',
            'old_id',
            'tier_price',
            'media_gallery'
        );

        $entityTypeId = Mage::getSingleton('eav/config')->getEntityType('catalog_product')->getId();
        $productAttributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($entityTypeId)
            ->load()->getIterator();

        $attributes = array(
            'store'             => 'store',
            'sku'               => 'sku',
            'attribute_set'     => 'attribute_set',
            'type'              => 'type',
            'name'              => 'name',
            'description'       => 'description',
            'short_description' => 'short_description',
            'weight'            => 'weight',
            'price'             => 'price'
        );
        foreach ($productAttributes as $attr) {
            $code = $attr->getAttributeCode();
            if (in_array($code, $internal) || $attr->getFrontendInput()=='hidden') {
                continue;
            }
            $attributes[$code] = $code;
        }
        foreach ($this->_inventoryFields as $field) {
            $attributes[$field] = $field;
        }
        return $attributes;
    }

}
