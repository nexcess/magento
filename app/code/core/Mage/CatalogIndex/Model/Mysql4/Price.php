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
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_CatalogIndex_Model_Mysql4_Price extends Mage_CatalogIndex_Model_Mysql4_Abstract
{
    protected $_rate = 1;
    protected $_taxRates = null;

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
        if (!$this->_rate) {
            $this->_rate = 1;
        }
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

    protected function _getTaxRateConditions()
    {
        return Mage::helper('tax')->getPriceTaxSql('main_table.value', 'IFNULL(tax_class_c.value, tax_class_d.value)');
    }

    protected function _joinTaxClass(&$select)
    {
        $taxClassAttribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', 'tax_class_id');
        $select->join(array('tax_class_d'=>$taxClassAttribute->getBackend()->getTable()), "tax_class_d.entity_id = main_table.entity_id AND tax_class_d.attribute_id = '{$taxClassAttribute->getId()}' AND tax_class_d.store_id = 0", array());
        $select->joinLeft(array('tax_class_c'=>$taxClassAttribute->getBackend()->getTable()), "tax_class_c.entity_id = main_table.entity_id AND tax_class_c.attribute_id = '{$taxClassAttribute->getId()}' AND tax_class_c.store_id = '{$this->getStoreId()}'", array());
    }

    public function getMaxValue($attribute = null, $entityIdsFilter = array())
    {
        $select = $this->_getReadAdapter()->select();

        $select->from(array('main_table'=>$this->getMainTable()), "MAX(((main_table.value{$this->_getTaxRateConditions()})*{$this->getRate()}))")
            ->where('main_table.entity_id in (?)', $entityIdsFilter)
            ->where('main_table.store_id = ?', $this->getStoreId())
            ->where('main_table.attribute_id = ?', $attribute->getId());
        $this->_joinTaxClass($select);

        if ($attribute->getAttributeCode() == 'price')
            $select->where('main_table.customer_group_id = ?', $this->getCustomerGroupId());

        return $this->_getReadAdapter()->fetchOne($select);
    }

    public function getCount($range, $attribute, $entityIdsFilter)
    {
        $select = $this->_getReadAdapter()->select();

        $fields = array('count'=>'COUNT(DISTINCT main_table.entity_id)', 'range'=>"FLOOR(((main_table.value{$this->_getTaxRateConditions()})*{$this->getRate()})/{$range})+1");

        $select->from(array('main_table'=>$this->getMainTable()), $fields)
            ->group('range')
            ->where('main_table.entity_id in (?)', $entityIdsFilter)
            ->where('main_table.store_id = ?', $this->getStoreId())
            ->where('main_table.attribute_id = ?', $attribute->getId());
        $this->_joinTaxClass($select);

        if ($attribute->getAttributeCode() == 'price')
            $select->where('main_table.customer_group_id = ?', $this->getCustomerGroupId());

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

        $select->from(array('main_table'=>$this->getMainTable()), 'main_table.entity_id')
            ->distinct(true)
            ->where('main_table.entity_id in (?)', $entityIdsFilter)
            ->where('main_table.store_id = ?', $this->getStoreId())
            ->where('main_table.attribute_id = ?', $attribute->getId());

        $this->_joinTaxClass($select);
        if ($attribute->getAttributeCode() == 'price')
            $select->where('main_table.customer_group_id = ?', $this->getCustomerGroupId());

        $select->where("((main_table.value{$this->_getTaxRateConditions()})*{$this->getRate()}) >= ?", ($index-1)*$range);
        $select->where("((main_table.value{$this->_getTaxRateConditions()})*{$this->getRate()}) < ?", $index*$range);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function getMinimalPrices($productIds)
    {
        $select = $this->_getReadAdapter()->select()
            ->from(array('main_table'=>$this->getTable('catalogindex/minimal_price')), array('main_table.entity_id', 'value'=>"(main_table.value)"))
            ->where('main_table.store_id = ?', $this->getStoreId())
            ->where('main_table.customer_group_id = ?', $this->getCustomerGroupId())
            ->where('main_table.entity_id IN(?)', $productIds);
        $this->_joinTaxClass($select);
        return $this->_getReadAdapter()->fetchAll($select);
    }
}