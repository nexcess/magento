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
 * Price index resource model
 *
 */
class Mage_CatalogIndex_Model_Mysql4_Price extends Mage_CatalogIndex_Model_Mysql4_Abstract
{
    protected $_rate = 1;

    protected function _construct()
    {
        $this->_init('catalogindex/price', 'index_id');
    }

    public function setRate($rate)
    {
        $this->_rate = $rate;
    }

    public function getRate()
    {
        return $this->_rate;
    }

    public function setCustomerGroupId($customerGroupId)
    {
        $this->_customerGroupId = $customerGroupId;
    }

    public function getCustomerGroupId()
    {
        return $this->_customerGroupId;
    }

    public function getMaxValue($attribute = null, $entityIdsFilter = array())
    {
        $select = $this->_getReadAdapter()->select();

        $select->from($this->getMainTable(), "MAX((value*{$this->getRate()}))")
            ->where('entity_id in (?)', $entityIdsFilter)
            ->where('store_id = ?', $this->getStoreId())
            ->where('attribute_id = ?', $attribute->getId());

        if ($attribute->getAttributeCode() == 'price')
            $select->where('customer_group_id = ?', $this->getCustomerGroupId());

        return $this->_getReadAdapter()->fetchOne($select);
    }

    public function getCount($range, $attribute, $entityIdsFilter)
    {
        $select = $this->_getReadAdapter()->select();

        $fields = array('count'=>'COUNT(DISTINCT entity_id)', 'range'=>"FLOOR((value*{$this->getRate()})/{$range})+1");

        $select->from($this->getMainTable(), $fields)
            ->group('range')
            ->where('entity_id in (?)', $entityIdsFilter)
            ->where('store_id = ?', $this->getStoreId())
            ->where('attribute_id = ?', $attribute->getId());

        if ($attribute->getAttributeCode() == 'price')
            $select->where('customer_group_id = ?', $this->getCustomerGroupId());

        $result = $this->_getReadAdapter()->fetchAll($select);

        $counts = array();
        foreach ($result as $row) {
            $counts[$row['range']] = $row['count'];
        }

        return $counts;
    }

    public function getFilteredEntities($range, $index, $attribute, $entityIdsFilter)
    {
        $select = $this->_getReadAdapter()->select();

        $select->from($this->getMainTable(), 'entity_id')
            ->distinct(true)
            ->where('entity_id in (?)', $entityIdsFilter)
            ->where('store_id = ?', $this->getStoreId())
            ->where('attribute_id = ?', $attribute->getId());

        if ($attribute->getAttributeCode() == 'price')
            $select->where('customer_group_id = ?', $this->getCustomerGroupId());

        $select->where("(value*{$this->getRate()}) >= ?", ($index-1)*$range);
        $select->where("(value*{$this->getRate()}) < ?", $index*$range);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function getMinimalPrices($productIds)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('catalogindex/minimal_price'), array('entity_id', 'value'))
            ->where('store_id = ?', $this->getStoreId())
            ->where('customer_group_id = ?', $this->getCustomerGroupId())
            ->where('entity_id IN(?)', $productIds);
        return $this->_getReadAdapter()->fetchAll($select);
    }
}