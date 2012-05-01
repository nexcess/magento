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
 * Catalog category model
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Category extends Mage_Catalog_Model_Resource_Eav_Mysql4_Abstract
{
    /**
     * Category tree object
     *
     * @var Varien_Data_Tree_Db
     */
    protected $_tree;

    /**
     * Catalog products table name
     *
     * @var string
     */
    protected $_categoryProductTable;

    public function __construct()
    {
        $resource = Mage::getSingleton('core/resource');
        $this->setType('catalog_category')
            ->setConnection(
                $resource->getConnection('catalog_read'),
                $resource->getConnection('catalog_write')
            );
        $this->_categoryProductTable = $this->getTable('catalog/category_product');
    }

    /**
     * Retrieve category tree object
     *
     * @return Varien_Data_Tree_Db
     */
    protected function _getTree()
    {
        if (!$this->_tree) {
            $this->_tree = Mage::getResourceModel('catalog/category_tree')
                ->load();
        }
        return $this->_tree;
    }

    protected function _beforeDelete(Varien_Object $object){
        parent::_beforeDelete($object);
        if ($child = $this->_getTree()->getNodeById($object->getId())) {
            $children = $child->getChildren();
            foreach ($children as $child) {
                $childObject = Mage::getModel('catalog/category')->load($child->getId())->delete();
            }
        }

        return $this;
    }

    protected function _beforeSave(Varien_Object $object)
    {
        parent::_beforeSave($object);

        if (!$object->getId()) {
            $object->setPosition($this->_getMaxPosition($object->getPath()) + 1);
            $object->setPath($object->getPath() . '/');
        }
        return $this;
    }

    protected function _afterSave(Varien_Object $object)
    {
        $this->_saveCategoryProducts($object);

        /**
         * Add identifier for new category
         */
        if (substr($object->getPath(), -1) == '/') {
            $object->setPath($object->getPath() . $object->getId());
            $this->_savePath($object);
            //$this->save($object);
        }

        return parent::_afterSave($object);
    }

    protected function _savePath($object)
    {
        if ($object->getId()) {
            $this->_getWriteAdapter()->update($this->getEntityTable(),
                array('path'=>$object->getPath()),
                $this->_getWriteAdapter()->quoteInto('entity_id=?', $object->getId())
            );
        }
        return $this;
    }

    protected function _getMaxPosition($path)
    {
        $select = $this->getReadConnection()->select();
        $select->from($this->getTable('catalog/category'), 'MAX(position)');
        $select->where('path ?', new Zend_Db_Expr("regexp '{$path}/[0-9]+\$'"));

        $result = 0;
        try {
            $result = (int) $this->getReadConnection()->fetchOne($select);
        } catch (Exception $e) {

        }
        return $result;
    }

    protected function _saveInStores(Varien_Object $object)
    {
        if (!$object->getMultistoreSaveFlag()) {
            $stores = $object->getStoreIds();
            foreach ($stores as $storeId) {
                if ($object->getStoreId() != $storeId) {
                    $newObject = clone $object;
                    $newObject->setStoreId($storeId)
                       ->setMultistoreSaveFlag(true)
                       ->save();
                }
            }
        }
        return $this;
    }

    /**
     * save category products
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Mage_Catalog_Model_Entity_Category
     */
    protected function _saveCategoryProducts($category)
    {
        $category->setIsChangedProductList(false);
        // new category-product relationships
        $products = $category->getPostedProducts();

        // no category-product updates requested, returning
        if (is_null($products)) {
            return $this;
        }

        $catId = $category->getId();

        $prodTable = $this->getTable('catalog/product');

        // old category-product relationships
        $oldProducts = $category->getProductsPosition();

        $insert = array_diff_key($products, $oldProducts);
        $delete = array_diff_key($oldProducts, $products);
        $update = array_intersect_key($products, $oldProducts);

        $write = $this->getWriteConnection();
        $updateProducts = array();

        if (!empty($delete)) {
            $write->delete($this->_categoryProductTable,
                $write->quoteInto('product_id in(?)', array_keys($delete)) .
                $write->quoteInto(' AND category_id=?', $catId)
            );
            $select = $write->select()
                ->from($prodTable, array('entity_id', 'category_ids'))
                ->where('entity_id IN (?)', array_keys($delete));
            $prods = $write->fetchPairs($select);
            foreach ($prods as $k=>$v) {
                $a = !empty($v) ? explode(',', $v) : array();
                $key = array_search($catId, $a);
                if ($key!==false) {
                    unset($a[$key]);
                }
                $updateProducts[$k] = "when ".(int)$k." then '".implode(',', array_unique($a))."'";
            }
        }

        if (!empty($insert)) {
            $insertSql = array();
            foreach ($insert as $k=>$v) {
                $insertSql[] = '('.(int)$catId.','.(int)$k.','.(int)$v.')';
            }

            $write->query("insert into {$this->_categoryProductTable} (category_id, product_id, position) values ".join(',', $insertSql));

            $select = $write->select()
                ->from($prodTable, array('entity_id', 'category_ids'))
                ->where('entity_id IN (?)', array_keys($insert));

            $prods = $write->fetchPairs($select);
            foreach ($prods as $k=>$v) {
                $a = !empty($v) ? explode(',', $v) : array();
                $a[] = (int)$catId;
                $updateProducts[$k] = "when ".(int)$k." then '".implode(',', array_unique($a))."'";
            }
        }

        if (!empty($updateProducts)) {
            $write->update($prodTable,
                array('category_ids'=>new Zend_Db_Expr('case entity_id '.join(' ', $updateProducts).' end')),
                $write->quoteInto('entity_id in (?)', array_keys($updateProducts))
            );
        }

        if (!empty($update)) {
            $updateProductsPosition = array();
            foreach ($update as $k=>$v) {
                if ($v!=$oldProducts[$k]) {
                    $updateProductsPosition[$k] = 'when '.(int)$k.' then '.(int)$v;
                }
            }
            if (!empty($updateProductsPosition)) {
                $write->update($this->_categoryProductTable,
                    array('position'=>new Zend_Db_Expr('case product_id '.join(' ', $updateProductsPosition).' end')),
                    $write->quoteInto('product_id in (?)', array_keys($updateProductsPosition))
                    .' and '.$write->quoteInto('category_id=?', $catId)
                );
            }
        }

        if (!empty($insert) || !empty($update) || !empty($delete)) {
            $category->setIsChangedProductList(true);
        }

        return $this;
    }

    protected function _updateCategoryPath($category, $path)
    {
        return $this;
        if ($category->getNotUpdateDepends()) {
            return $this;
        }
        foreach ($path as $pathItem) {
            if ($pathItem->getId()>1 && $category->getId() != $pathItem->getId()) {
                $category = Mage::getModel('catalog/category')
                    ->load($pathItem->getId())
                    ->save();
            }
        }
        return $this;
    }

    public function getStoreIds($category)
    {
        if (!$category->getId()) {
            return array();
        }

        $nodePath = $this->_getTree()
            ->getNodeById($category->getId())
                ->getPath();

        $nodes = array();
        foreach ($nodePath as $node) {
            $nodes[] = $node->getId();
        }

        $stores = array();
        $storeCollection = Mage::getModel('core/store')->getCollection()->loadByCategoryIds($nodes);
        foreach ($storeCollection as $store) {
            $stores[$store->getId()] = $store->getId();
        }

        $entityStoreId = $this->getStoreId();
        if (!in_array($entityStoreId, $stores)) {
            array_unshift($stores, $entityStoreId);
        }
        if (!in_array(0, $stores)) {
            array_unshift($stores, 0);
        }
        return $stores;
    }

    /**
     * Retrieve category product id's
     *
     * @param   Mage_Catalog_Model_Category $category
     * @return  array
     */
    public function getProductsPosition($category)
    {
        $select = $this->_getWriteAdapter()->select()
            ->from($this->_categoryProductTable, array('product_id', 'position'))
            ->where('category_id=?', $category->getId());
        $positions = $this->_getWriteAdapter()->fetchPairs($select);
        return $positions;
    }

    public function move(Mage_Catalog_Model_Category $category, $newParentId)
    {
        $oldStoreId = $category->getStoreId();
        $parent = Mage::getModel('catalog/category')
            ->setStoreId($category->getStoreId())
            ->load($category->getParentId());

        $newParent = Mage::getModel('catalog/category')
            ->setStoreId($category->getStoreId())
            ->load($newParentId);

        $oldParentStores = $parent->getStoreIds();
        $newParentStores = $newParent->getStoreIds();

        $category->setParentId($newParentId)
            ->save();
        $parent->save();
        $newParent->save();

        // Add to new stores
        $addToStores = array_diff($newParentStores, $oldParentStores);
        foreach ($addToStores as $storeId) {
            $newCategory = clone $category;
            $newCategory->setStoreId($storeId)
               ->save();
            $children = $category->getAllChildren();

            if ($children && $arrChildren = explode(',', $children)) {
                foreach ($arrChildren as $childId) {
                    if ($childId == $category->getId()) {
                        continue;
                    }

                    $child = Mage::getModel('catalog/category')
                       ->setStoreId($oldStoreId)
                       ->load($childId)
                       ->setStoreId($storeId)
                       ->save();
                }
            }
        }
        return $this;
    }

    public function checkId($id)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getEntityTable(), 'entity_id')
            ->where('entity_id=?', $id);
        return $this->_getReadAdapter()->fetchOne($select);
    }

    public function verifyIds(array $ids)
    {
        $validIds = array();
        $select = $this->_getWriteAdapter()->select()
            ->from($this->getEntityTable(), 'entity_id')
            ->where('entity_id IN(?)', $ids);
        $query = $this->_getWriteAdapter()->query($select);
        while ($row = $query->fetch()) {
            $validIds[] = $row['entity_id'];
        }
        return $validIds;
    }

    public function getChildrenAmount($category, $isActiveFlag = true)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getEntityTable(), array('COUNT(entity_id)'))
            ->where('path like ?', $category->getPath() . '/%')
            ->where('is_active = ?', $isActiveFlag);

        return $this->_getReadAdapter()->fetchOne($select);
    }
}