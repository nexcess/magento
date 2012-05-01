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
 * @package    Mage_CatalogIndex
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Reindexer resource model
 *
 */
class Mage_CatalogIndex_Model_Mysql4_Indexer extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_attributeCache = array();
    protected $_tierCache = array();
    protected $_customerGroups = null;
//    protected $_ruleCache = array();

    const REINDEX_CHILDREN_NONE = 0;
    const REINDEX_CHILDREN_ALL = 1;
    const REINDEX_CHILDREN_CONFIGURABLE = 2;
    const REINDEX_CHILDREN_GROUPED = 3;


    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }

    public function clear()
    {
        $this->_getWriteAdapter()->query("TRUNCATE TABLE {$this->getTable('catalogindex/eav')}");
        $this->_getWriteAdapter()->query("TRUNCATE TABLE {$this->getTable('catalogindex/price')}");
        $this->_getWriteAdapter()->query("TRUNCATE TABLE {$this->getTable('catalogindex/minimal_price')}");
    }

    protected function _where($select, $field, $condition)
    {
        if (is_array($condition) && isset($condition['or'])) {
            $select->where("{$field} in (?)", $condition['or']);
        } elseif (is_array($condition)) {
            foreach ($condition as $where)
                $select->where("{$field} = ?", $where);
        } else {
            $select->where("{$field} = ?", $condition);
        }
    }

    public function getProductData($products, $attributeIds, $store){
        $suffixes = array('decimal', 'varchar', 'int', 'text', 'datetime');
        if (!is_array($products)) {
            $products = new Zend_Db_Expr($products);
        }
        $result = array();
        foreach ($suffixes as $suffix) {
            $tableName = "{$this->getTable('catalog/product')}_{$suffix}";
            $condition = "product.entity_id = c.entity_id AND c.store_id = {$store->getId()} AND c.attribute_id = d.attribute_id";
            $defaultCondition = "product.entity_id = d.entity_id AND d.store_id = 0";
            $fields = array('entity_id', 'type_id', 'attribute_id'=>'IFNULL(c.attribute_id, d.attribute_id)', 'value'=>'IFNULL(c.value, d.value)');

            $select = $this->_getReadAdapter()->select()
                ->from(array('product'=>$this->getTable('catalog/product')), $fields)
                ->where('product.entity_id in (?)', $products)
                ->joinRight(array('d'=>$tableName), $defaultCondition, array())
                ->joinLeft(array('c'=>$tableName), $condition, array())
                ->where('c.attribute_id IN (?) OR d.attribute_id IN (?)', $attributeIds);

            $part = $this->_getReadAdapter()->fetchAll($select);

            if (is_array($part)) {
                $result = array_merge($result, $part);
            }
        }

        return $result;
    }

    public function getTierData($products, $store){
        if (isset($this->_tierCache[$store->getWebsiteId()])) {
            return $this->_tierCache[$store->getWebsiteId()];
        }

        $suffixes = array('tier_price');
        if (!is_array($products)) {
            $products = new Zend_Db_Expr($products);
        }

        $result = array();
        foreach ($suffixes as $suffix) {
            $tableName = "{$this->getTable('catalog/product')}_{$suffix}";
            $fields = array(
                'entity_id',
                'type_id',
                'c.customer_group_id',
                'c.qty',
                'c.value',
                'c.all_groups',
            );
            $condition = "product.entity_id = c.entity_id";

            $select = $this->_getReadAdapter()->select()
                ->from(array('product'=>$this->getTable('catalog/product')), $fields)
                ->joinLeft(array('c'=>$tableName), $condition, array())
                ->where('product.entity_id in (?)', $products)
                ->where('(c.website_id = ?', $store->getWebsiteId())
                ->orWhere('c.website_id = 0)');

            $part = $this->_getReadAdapter()->fetchAll($select);
            if (is_array($part)) {
                $result = array_merge($result, $part);
            }
        }

        $this->_tierCache[$store->getWebsiteId()] = $result;

        return $result;
    }

    public function reindexAttributes($products, $attributeIds, $store, $forcedId = null, $table = 'catalogindex/eav', $children = self::REINDEX_CHILDREN_ALL)
    {
        $query = "INSERT INTO {$this->getTable($table)} (entity_id, attribute_id, value, store_id) VALUES ";
        $attributeIndex = $this->getProductData($products, $attributeIds, $store);
        $rows = array();
        $total = count($attributeIndex);
        for ($i=0; $i<$total; $i++) {
            $index = $attributeIndex[$i];

            $type = $index['type_id'];
            $id = (is_null($forcedId) ? $index['entity_id'] : $forcedId);

            if ($type != Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && $children != self::REINDEX_CHILDREN_NONE) {
                if ($children != self::REINDEX_CHILDREN_ALL && $children != self::REINDEX_CHILDREN_CONFIGURABLE && $type == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                } elseif ($children != self::REINDEX_CHILDREN_ALL && $children != self::REINDEX_CHILDREN_GROUPED && $type == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                } else {
                    $childrenIds = $this->getProductChildrenFilter($index['entity_id'], $type);
                    $this->reindexAttributes($childrenIds, $attributeIds, $store, $id, $table);
                }


            }

            if ($id && $index['attribute_id'] && $index['value']) {
                if ($this->_getAttribute($index['attribute_id'])->getFrontendInput() == 'multiselect') {
                    $index['value'] = explode(',', $index['value']);
                }

                if (is_array($index['value'])) {
                    foreach ($index['value'] as $value) {
                        $rows[] = '(' . implode(',',array($id, $index['attribute_id'], $value, $store->getId())) . ')';
                    }
                } else {
                    $rows[] = '(' . implode(',',array($id, $index['attribute_id'], $index['value'], $store->getId())) . ')';
                }
            }

            if ($i+1 == $total || count($rows) >= 100) {
                if ($rows) {
                    $this->_getWriteAdapter()->query($query . implode(',', $rows));
                    $rows = array();
                }
            }
        }
    }

    protected function _getAttribute($attributeId, $idIsCode = false)
    {
        $key = $attributeId . '|' . intval($idIsCode);
        if (!isset($this->_attributeCache[$key])) {
            $attribute = Mage::getModel('eav/entity_attribute');
            if ($idIsCode) {
                $attribute->loadByCode('catalog_product', $attributeId);
            } else {
                $attribute->load($attributeId);
            }
            $this->_attributeCache[$key] = $attribute;
        }

        return $this->_attributeCache[$key];
    }

    protected function _getGroups()
    {
        if (is_null($this->_customerGroups)) {
            $this->_customerGroups = Mage::getModel('customer/group')->getCollection();
        }
        return $this->_customerGroups;
    }

    public function reindexTiers($products, $store, $forcedId = null)
    {
        $attribute = $this->_getAttribute('tier_price', true);

        $query = "INSERT INTO {$this->getTable('catalogindex/price')} (entity_id, attribute_id, value, store_id, customer_group_id, qty) VALUES ";
        $attributeIndex = $this->getTierData($products, $store);
        $rows = array();
        $total = count($attributeIndex);
        for ($i=0; $i<$total; $i++) {
            $index = $attributeIndex[$i];

            $type = $index['type_id'];
            $id = (is_null($forcedId) ? $index['entity_id'] : $forcedId);
/*
            if ($type == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                $childrenIds = $this->getProductChildrenFilter($id, $type);

                $this->reindexTiers($childrenIds, $store, $id);
            }
*/
            if ($id && $index['value']) {
                if ($index['all_groups'] == 1) {
                    foreach ($this->_getGroups() as $group) {
                        $rows[] = '(' . implode(',',array($id, $attribute->getId(), $index['value'], $store->getId(), (int) $group->getId(), (int) $index['qty'])) . ')';
                    }
                } else {
                    $rows[] = '(' . implode(',',array($id, $attribute->getId(), $index['value'], $store->getId(), (int) $index['customer_group_id'], (int) $index['qty'])) . ')';
                }
            }
            if ($i+1 == $total || count($rows) >= 100) {
                if ($rows)
                    $this->_getWriteAdapter()->query($query . implode(',', $rows));
                $rows = array();
            }
        }
    }

    public function reindexMinimalPrices($products, $store)
    {
        $tierAttribute = $this->_getAttribute('tier_price', true);
        $priceAttribute = $this->_getAttribute('price', true);
    }

    public function reindexPrices($products, $attributeIds, $store)
    {
        $this->reindexAttributes($products, $attributeIds, $store, null, 'catalogindex/price', self::REINDEX_CHILDREN_ALL);
    }

    public function reindexFinalPrices($products, $store, $forcedId = null)
    {
        $priceAttribute = $this->_getAttribute('price', true);
        $insert = array();
        $total = count($products);
        $query = "INSERT INTO {$this->getTable('catalogindex/price')} (entity_id, store_id, customer_group_id, value, attribute_id) VALUES ";
        for ($i=0; $i<$total; $i++) {
            $product = $products[$i];
            foreach ($this->_getGroups() as $group) {
                $finalPrice = $this->_processFinalPrice($product, $store, $group);

                if (!is_null($forcedId))
                    $product = $forcedId;
                if (false !== $finalPrice && false !== $product && false !== $store->getId() && false !== $group->getId() && false !== $priceAttribute->getId()) {
                    $insert[] = '(' . implode(',', array($product, $store->getId(), $group->getId(), $finalPrice, $priceAttribute->getId())) . ')';
                }
            }
            if ($i+1 == $total || count($insert) >= 100) {
                if ($insert) {
                    $sql = $query . implode(',', $insert);
                    $this->_getWriteAdapter()->query($sql);
                }
                $insert = array();
            }
        }

        if (is_null($forcedId)) {
            $select = $this->_getReadAdapter()
                ->select()
                ->from($this->getTable('catalog/product'), array('entity_id'))
                ->where('entity_id in (?)', $products)
                ->where('type_id = ?', Mage_Catalog_Model_Product_Type::TYPE_GROUPED);
            $groupedProducts = $this->_getReadAdapter()->fetchCol($select);
            foreach ($groupedProducts as $product) {
                $children = $this->_getReadAdapter()->fetchCol($this->getProductChildrenFilter($product, Mage_Catalog_Model_Product_Type::TYPE_GROUPED));
                $this->reindexFinalPrices(
                    $children,
                    $store,
                    $product
                );
            }
        }
    }

    protected function _processFinalPrice($productId, $store, $group)
    {
        $priceAttribute = $this->_getAttribute('price', true);
        $specialPriceAttribute = $this->_getAttribute('special_price', true);
        $specialPriceFromAttribute = $this->_getAttribute('special_from_date', true);
        $specialPriceToAttribute = $this->_getAttribute('special_to_date', true);

        $basePrice = $this->_getAttributeValue($productId, $store, $priceAttribute);
        $specialPrice = $this->_getAttributeValue($productId, $store, $specialPriceAttribute);
        $specialPriceFrom = $this->_getAttributeValue($productId, $store, $specialPriceFromAttribute);
        $specialPriceTo = $this->_getAttributeValue($productId, $store, $specialPriceToAttribute);

        $finalPrice = $basePrice;

        $today = floor(time()/86400)*86400;
        $from = floor(strtotime($specialPriceFrom)/86400)*86400;
        $to = floor(strtotime($specialPriceTo)/86400)*86400;

        if ($specialPrice !== false) {
            if ($specialPriceFrom && $today < $from) {
            } elseif ($specialPriceTo && $today > $to) {
            } else {
               $finalPrice = min($finalPrice, $specialPrice);
            }
        }

        $date = mktime(0,0,0);
        $wId = $store->getWebsiteId();
        $gId = $group->getId();

        $rulePrice = Mage::getResourceModel('catalogrule/rule')->getRulePrice($date, $wId, $gId, $productId);
        if ($rulePrice !== false) {
            $finalPrice = min($finalPrice, $rulePrice);
        }
        return $finalPrice;
    }

    protected function _getAttributeValue($productId, $store, $attribute)
    {
        $tableName = "{$this->getTable('catalog/product')}_{$attribute->getBackendType()}";

        $condition = "product.entity_id = c.entity_id AND c.store_id = {$store->getId()}";
        $defaultCondition = "product.entity_id = d.entity_id AND d.store_id = 0";

        $select = $this->_getReadAdapter()->select()
            ->from(array('product'=>$this->getTable('catalog/product')), 'IFNULL(c.value, d.value)')
            ->where('product.entity_id = ?', $productId)
            ->joinLeft(array('c'=>$tableName), $condition, array())
            ->joinLeft(array('d'=>$tableName), $defaultCondition, array())
            ->where('IFNULL(c.attribute_id, d.attribute_id) = ?', $attribute->getId());


        return $this->_getReadAdapter()->fetchOne($select);
    }

    public function getProductChildrenFilter($id, $type)
    {
        $select = $this->_getReadAdapter()->select();
        switch ($type){
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                $table = $this->getTable('catalog/product_link');
                $field = 'linked_product_id';
                $searchField = 'product_id';
                $select->where("link_type_id = ?", Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED);
                break;

            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                $table = $this->getTable('catalog/product_super_link');
                $field = 'product_id';
                $searchField = 'parent_id';
                break;

            default:
                return false;
        }
        $select->from($table, $field)
            ->where("$searchField = ?", $id);

        return $select;
    }
}