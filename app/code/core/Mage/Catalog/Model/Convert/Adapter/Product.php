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


class Mage_Catalog_Model_Convert_Adapter_Product
    extends Mage_Eav_Model_Convert_Adapter_Entity
{
    const MULTI_DELIMITER = ' , ';

    /**
     * Product model
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_productModel;

    /**
     * product types collection array
     *
     * @var array
     */
    protected $_productTypes;

    /**
     * product attribute set collection array
     *
     * @var array
     */
    protected $_productAttributeSets;

    protected $_stores;

    protected $_attributes = array();

    protected $_configs = array(
        'min_qty', 'backorders', 'min_sale_qty', 'max_sale_qty'
    );

    protected $_requiredFields = array(
        'name', 'description', 'short_description', 'weight', 'price',
        'tax_class_id'
    );

    protected $_ignoreFields = array(
        'entity_id', 'attribute_set', 'attribute_set_id', 'type', 'type_id',
        'category_ids'
    );

    protected $_imageFields = array(
        'image', 'small_image', 'thumbnail'
    );

    protected $_inventorySimpleFields = array(
        'qty', 'min_qty', 'use_config_min_qty', 'is_qty_decimal', 'backorders',
        'use_config_backorders', 'min_sale_qty', 'use_config_min_sale_qty',
        'max_sale_qty', 'use_config_max_sale_qty', 'is_in_stock',
        'notify_stock_qty', 'use_config_notify_stock_qty'
    );

    protected $_inventoryOtherFields = array(
        'is_in_stock',
    );

    /**
     * Load product collection Id(s)
     *
     */
    public function load()
    {
        $attrFilterArray = array();
        $attrFilterArray ['name']           = 'like';
        $attrFilterArray ['sku']            = 'like';
        $attrFilterArray ['type']           = 'eq';
        $attrFilterArray ['attribute_set']  = 'eq';
        $attrFilterArray ['visibility']     = 'eq';
        $attrFilterArray ['status']         = 'eq';
        $attrFilterArray ['price']          = 'fromTo';
        $attrFilterArray ['qty']            = 'fromTo';
        $attrFilterArray ['store_id']       = 'eq';

        $attrToDb = array(
            'type'          => 'type_id',
            'attribute_set' => 'attribute_set_id'
        );

        $filters = $this->_parseVars();

        if ($qty = $this->getFieldValue($filters, 'qty')) {
            $qtyFrom = isset($qty['from']) ? $qty['from'] : 0;
            $qtyTo   = isset($qty['to']) ? $qty['to'] : 0;

            $qtyAttr = array();
            $qtyAttr['alias']       = 'qty';
            $qtyAttr['attribute']   = 'cataloginventory/stock_item';
            $qtyAttr['field']       = 'qty';
            $qtyAttr['bind']        = 'product_id=entity_id';
            $qtyAttr['cond']        = "{{table}}.qty between '{$qtyFrom}' AND '{$qtyTo}'";
            $qtyAttr['joinType']    = 'inner';

            $this->setJoinFeild($qtyAttr);
        }

        parent::setFilter($attrFilterArray, $attrToDb);

        if ($price = $this->getFieldValue($filters, 'price')) {
            $this->_filter[] = array(
                'attribute' => 'price',
                'from'      => $price['from'],
                'to'        => $price['to']
            );
            $this->setJoinAttr(array(
                'alias'     => 'price',
                'attribute' => 'catalog_product/price',
                'bind'      => 'entity_id',
                'joinType'  => 'LEFT'
            ));
        }

        return parent::load();
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
        if ($this->_attributes[$code] instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
            $applyTo = $this->_attributes[$code]->getApplyTo();
            if ($applyTo && !in_array($this->getProductModel()->getTypeId(), $applyTo)) {
                return false;
            }
        }
        return $this->_attributes[$code];
    }

    /**
     * Retrieve product type collection array
     *
     * @return array
     */
    public function getProductTypes()
    {
        if (is_null($this->_productTypes)) {
            $this->_productTypes = array();
            $options = Mage::getModel('catalog/product_type')
                ->getOptionArray();
            foreach ($options as $k => $v) {
                $this->_productTypes[$k] = $k;
            }
        }
        return $this->_productTypes;
    }

    /**
     * Retrieve product attribute set collection array
     *
     * @return array
     */
    public function getProductAttributeSets()
    {
        if (is_null($this->_productAttributeSets)) {
            $this->_productAttributeSets = array();

            $entityTypeId = Mage::getModel('eav/entity')
                ->setType('catalog_product')
                ->getTypeId();
            $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter($entityTypeId);
            foreach ($collection as $set) {
                $this->_productAttributeSets[$set->getAttributeSetName()] = $set->getId();
            }
        }
        return $this->_productAttributeSets;
    }

    /**
     * Retrieve store object by code
     *
     * @param string $store
     * @return Mage_Core_Model_Store
     */
    public function getStoreByCode($store)
    {
        if (is_null($this->_stores)) {
            $this->_stores = Mage::app()->getStores(true, true);
        }
        if (isset($this->_stores[$store])) {
            return $this->_stores[$store];
        }
        return false;
    }

    public function parse()
    {
        $batchModel = Mage::getSingleton('dataflow/batch');
        /* @var $batchModel Mage_Dataflow_Model_Batch */

        $batchImportModel = $batchModel->getBatchImportModel();
        $importIds = $batchImportModel->getIdCollection();

        foreach ($importIds as $importId) {
            //print '<pre>'.memory_get_usage().'</pre>';
            $batchImportModel->load($importId);
            $importData = $batchImportModel->getBatchData();

            $this->saveRow($importData);
        }
    }

    protected $_productId = '';

    /**
     * Initialize convert adapter model for products collection
     *
     */
    public function __construct()
    {
        $this->setVar('entity_type', 'catalog/product');
        if (!Mage::registry('Object_Cache_Product')) {
            $this->setProduct(Mage::getModel('catalog/product'));
        }

        if (!Mage::registry('Object_Cache_StockItem')) {
            $this->setStockItem(Mage::getModel('cataloginventory/stock_item'));
        }
    }

    /**
     * Retrieve not loaded collection
     *
     * @param string $entityType
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function _getCollectionForLoad($entityType)
    {
        $collection = parent::_getCollectionForLoad($entityType)
            ->setStoreId($this->getStoreId())
            ->addStoreFilter($this->getStoreId());
        return $collection;
    }

    public function setProduct(Mage_Catalog_Model_Product $object)
    {
        $id = Varien_Object_Cache::singleton()->save($object);
        //$this->_product = $object;
        Mage::register('Object_Cache_Product', $id);
    }

    public function getProduct()
    {
        return Varien_Object_Cache::singleton()->load(Mage::registry('Object_Cache_Product'));
    }

    public function setStockItem(Mage_CatalogInventory_Model_Stock_Item $object)
    {
        $id = Varien_Object_Cache::singleton()->save($object);
        //$this->_product = $object;
        Mage::register('Object_Cache_StockItem', $id);

        //$this->_stockItem = $object;
    }

    public function getStockItem()
    {
        return Varien_Object_Cache::singleton()->load(Mage::registry('Object_Cache_StockItem'));
        //return $this->_stockItem;
    }

    public function save()
    {
        $stores = array();
        foreach (Mage::getConfig()->getNode('stores')->children() as $storeNode) {
            $stores[(int)$storeNode->system->store->id] = $storeNode->getName();
        }

        $collections = $this->getData();
        if ($collections instanceof Mage_Catalog_Model_Entity_Product_Collection) {
            $collections = array($collections->getEntity()->getStoreId()=>$collections);
        } elseif (!is_array($collections)) {
            $this->addException(Mage::helper('catalog')->__('No product collections found'), Mage_Dataflow_Model_Convert_Exception::FATAL);
        }

        //$stockItems = $this->getInventoryItems();
        $stockItems = Mage::registry('current_imported_inventory');
        if ($collections) foreach ($collections as $storeId=>$collection) {
            $this->addException(Mage::helper('catalog')->__('Records for "'.$stores[$storeId].'" store found'));

            if (!$collection instanceof Mage_Catalog_Model_Entity_Product_Collection) {
                $this->addException(Mage::helper('catalog')->__('Product collection expected'), Mage_Dataflow_Model_Convert_Exception::FATAL);
            }
            try {
                $i = 0;
                foreach ($collection->getIterator() as $model) {
                    $new = false;
                    // if product is new, create default values first
                    if (!$model->getId()) {
                        $new = true;
                        $model->save();

                        // if new product and then store is not default
                        // we duplicate product as default product with store_id -
                        if (0 !== $storeId ) {
                            $data = $model->getData();
                            $default = Mage::getModel('catalog/product');
                            $default->setData($data);
                            $default->setStoreId(0);
                            $default->save();
                            unset($default);
                        } // end

                        #Mage::getResourceSingleton('catalog_entity/convert')->addProductToStore($model->getId(), 0);
                    }
                    if (!$new || 0!==$storeId) {
                        if (0!==$storeId) {
                            Mage::getResourceSingleton('catalog_entity/convert')->addProductToStore($model->getId(), $storeId);
                        }
                        $model->save();
                    }

                    if (isset($stockItems[$model->getSku()]) && $stock = $stockItems[$model->getSku()]) {
                        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($model->getId());
                        $stockItemId = $stockItem->getId();

                        if (!$stockItemId) {
                            $stockItem->setData('product_id', $model->getId());
                            $stockItem->setData('stock_id', 1);
                            $data = array();
                        } else {
                            $data = $stockItem->getData();
                        }

                        foreach($stock as $field => $value) {
                            if (!$stockItemId) {
                                if (in_array($field, $this->_configs)) {
                                    $stockItem->setData('use_config_'.$field, 0);
                                }
                                $stockItem->setData($field, $value?$value:0);
                            } else {

                                if (in_array($field, $this->_configs)) {
                                    if ($data['use_config_'.$field] == 0) {
                                        $stockItem->setData($field, $value?$value:0);
                                    }
                                } else {
                                    $stockItem->setData($field, $value?$value:0);
                                }
                            }
                        }
                        $stockItem->save();
                        unset($data);
                        unset($stockItem);
                        unset($stockItemId);
                    }
                    unset($model);
                    $i++;
                }
                $this->addException(Mage::helper('catalog')->__("Saved ".$i." record(s)"));
            } catch (Exception $e) {
                if (!$e instanceof Mage_Dataflow_Model_Convert_Exception) {
                    $this->addException(Mage::helper('catalog')->__('Problem saving the collection, aborting. Error: %s', $e->getMessage()),
                        Mage_Dataflow_Model_Convert_Exception::FATAL);
                }
            }
        }
        //unset(Zend::unregister('imported_stock_item'));
        unset($collections);
        return $this;
    }

    public function saveRow($importData)
    {
        $product = $this->getProductModel();
        $product->setId(null);

        if (empty($importData['store'])) {
            $message = Mage::helper('catalog')->__('Skip import row, required field "%s" not defined', 'store');
            Mage::throwException($message);
        }

        $store = $this->getStoreByCode($importData['store']);

        if ($store === false) {
            $message = Mage::helper('catalog')->__('Skip import row, store "%s" field not exists', $importData['store']);
            Mage::throwException($message);
        }
        if (empty($importData['sku'])) {
            $message = Mage::helper('catalog')->__('Skip import row, required field "%s" not defined', 'sku');
            Mage::throwException($message);
        }
        $product->setStoreId($store->getId());
        $productId = $product->getIdBySku($importData['sku']);

        if ($productId) {
            $product->load($productId);
        }
        else {
            $productTypes = $this->getProductTypes();
            $productAttributeSets = $this->getProductAttributeSets();

            /**
             * Check product define type
             */
            if (empty($importData['type']) || !isset($productTypes[strtolower($importData['type'])])) {
                $value = isset($importData['type']) ? $importData['type'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'type');
                Mage::throwException($message);
            }
            $product->setTypeId($productTypes[strtolower($importData['type'])]);
            /**
             * Check product define attribute set
             */
            if (empty($importData['attribute_set']) || !isset($productAttributeSets[$importData['attribute_set']])) {
                $value = isset($importData['attribute_set']) ? $importData['attribute_set'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'attribute_set');
                Mage::throwException($message);
            }
            $product->setAttributeSetId($productAttributeSets[$importData['attribute_set']]);

            foreach ($this->_requiredFields as $field) {
                if (!isset($importData[$field])) {
                    $message = Mage::helper('catalog')->__('Skip import row, required field "%s" for new products not defined', $field);
                    Mage::throwException($message);
                }
            }
        }

        if (isset($importData['category_ids'])) {
            $product->setCategoryIds($importData['category_ids']);
        }

        foreach ($this->_ignoreFields as $field) {
            if (isset($importData[$field])) {
                unset($importData[$field]);
            }
        }

        if ($store->getId() != 0) {
            $websiteIds = $product->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = array();
            }
            if (!in_array($store->getWebsiteId(), $websiteIds)) {
                $websiteIds[] = $store->getWebsiteId();
            }
            $product->setWebsiteIds($websiteIds);
        }

        foreach ($importData as $field => $value) {
            if (in_array($field, $this->_inventorySimpleFields)) {
                continue;
            }
            if (in_array($field, $this->_imageFields)) {
                continue;
            }

            $attribute = $this->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            $isArray = false;
            $setValue = $value;

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = split(self::MULTI_DELIMITER, $value);
                $isArray = true;
                $setValue = array();
            }

            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);

                if ($isArray) {
                    foreach ($options as $item) {
                        if (in_array($item['label'], $value)) {
                            $setValue[] = $item['value'];
                        }
                    }
                }
                else {
                    $setValue = null;
                    foreach ($options as $item) {
                        if ($item['label'] == $value) {
                            $setValue = $item['value'];
                        }
                    }
                }
            }

            $product->setData($field, $setValue);
        }

        if (!$product->getVisibility()) {
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        }

        $stockData = array();
        $inventoryFields = $product->getTypeId() == 'simple' ? $this->_inventorySimpleFields : $this->_inventoryOtherFields;
        foreach ($inventoryFields as $field) {
            if (isset($importData[$field])) {
                $stockData[$field] = $importData[$field];
            }
        }
        $product->setStockData($stockData);

        $imageData = array();
        foreach ($this->_imageFields as $field) {
            if (!empty($importData[$field]) && $importData[$field] != 'no_selection') {
                if (!isset($imageData[$importData[$field]])) {
                    $imageData[$importData[$field]] = array();
                }
                $imageData[$importData[$field]][] = $field;
            }
        }

        foreach ($imageData as $file => $fields) {
            try {
                $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . $file, $fields);
            }
            catch (Exception $e) {}
        }

        $product->save();

        return true;



//        static $import, $product, $stockItem;
        $mem = memory_get_usage(); $origMem = $mem; $memory = $mem;

//        if (!$product) {
//            $import = Mage::getModel('dataflow/import');
//            $product = Mage::getModel('catalog/product');
//            $stockItem = Mage::getModel('cataloginventory/stock_item');
//        }

        $product = $this->getProduct();
        $stockItem = $this->getStockItem();

        set_time_limit(240);

//        $row = unserialize($args['row']['value']);
        $row = $args;
        $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;



        $product->importFromTextArray($row);
        //echo '<pre>';
        //print_r($product->getData());
        $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;


        if (!$product->getData()) {
            return;
        }

        try {
            $product->save();
            $productId= $this->_productId = $product->getId();
            $product->unsetData();

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;
            if ($stockItem) {
                $stockItem->loadByProduct($productId);
                if (!$stockItem->getId()) {
                    $stockItem->setProductId($productId)->setStockId(1);
                }
                foreach ($row['row'] as $field=>$value) {
                    if (in_array($field, $this->_inventoryFields)) {
                        if ($value != '') $stockItem->setData($field, $value);
                    }
                }
                $stockItem->save();
                $stockItem->unsetData();
            }

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

            $newMem = memory_get_usage(); $memory .= ' = '.($newMem-$origMem); $mem = $newMem;


        } catch (Exception $e) {

        }
        unset($row);
        return array('memory'=>$memory);
    }

   public function saveRowSilently($args)
    {
//        static $import, $product, $stockItem;
        $mem = memory_get_usage(); $origMem = $mem; $memory = $mem;

//        if (!$product) {
//            $import = Mage::getModel('dataflow/import');
//            $product = Mage::getModel('catalog/product');
//            $stockItem = Mage::getModel('cataloginventory/stock_item');
//        }

        $product = $this->getProduct();
        $stockItem = $this->getStockItem();

        set_time_limit(240);

//        $row = unserialize($args['row']['value']);
        $row = $args;
        $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;



        $product->importFromTextArraySilently($row);
        //echo '<pre>';
        //print_r($product->getData());
        $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;


        if (!$product->getData()) {
            return;
        }

        try {
            $product->save();
            $productId= $this->_productId = $product->getId();
            $product->unsetData();

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;
            if ($stockItem) {
                $stockItem->loadByProduct($productId);
                if (!$stockItem->getId()) {
                    $stockItem->setProductId($productId)->setStockId(1);
                }
                foreach ($row['row'] as $field=>$value) {
                    if (in_array($field, $this->_inventoryFields)) {
                        if ($value != '') $stockItem->setData($field, $value);
                    }
                }
                $stockItem->save();
                $stockItem->unsetData();
            }

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

            $newMem = memory_get_usage(); $memory .= ' = '.($newMem-$origMem); $mem = $newMem;


        } catch (Exception $e) {

        }
        unset($row);
        return array('memory'=>$memory);
    }

    function setInventoryItems($items)
    {
        $this->_inventoryItems = $items;
    }

    function getInventoryItems()
    {
        return $this->_inventoryItems;
    }

    function getProductId()
    {
        return $this->_productId;
    }
}
