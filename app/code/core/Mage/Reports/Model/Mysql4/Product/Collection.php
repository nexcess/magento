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
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Products Report collection
 *
 * @category   Mage
 * @package    Mage_Reports
 */

class Mage_Reports_Model_Mysql4_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    protected $_productEntityId;
    protected $_productEntityTableName;
    protected $_productEntityTypeId;

    public function setProductEntityId($value)
    {
        $this->_productEntityId = $value;
        return $this;
    }

    public function getProductEntityId()
    {
        return $this->_productEntityId;
    }

    public function setProductEntityTableName($value)
    {
        $this->_productEntityTableName = $value;
        return $this;
    }

    public function getProductEntityTableName()
    {
        return $this->_productEntityTableName;
    }

    public function setProductEntityTypeId($value)
    {
        $this->_productEntityTypeId = $value;
        return $this;
    }

    public function getProductEntityTypeId()
    {
        return $this->_productEntityTypeId;
    }

    public function __construct()
    {
        $product = Mage::getResourceSingleton('catalog/product');
        /* @var $product Mage_Catalog_Model_Entity_Product */
        $this->setProductEntityId($product->getEntityIdField());
        $this->setProductEntityTableName($product->getEntityTable());
        $this->setProductEntityTypeId($product->getTypeId());

        parent::__construct();
    }

    protected function _joinFields()
    {
        $this->_totals = new Varien_Object();

        $this->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('price');
        /*$this->getSelect()->from('', array(
                    'viewed' => 'CONCAT("","")',
                    'added' => 'CONCAT("","")',
                    'purchased' => 'CONCAT("","")',
                    'fulfilled' => 'CONCAT("","")',
                    'revenue' => 'CONCAT("","")',
                   ));*/
    }

    public function getSelectCountSql()
    {
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::HAVING);
        $countSelect->from("", "count(DISTINCT e.entity_id)");
        $sql = $countSelect->__toString();
        return $sql;
    }

    public function addCartsCount()
    {
        $countSelect = clone $this->getSelect();
        $countSelect->reset();
        $quoteItem = Mage::getResourceSingleton('sales/quote_item');
        /* @var $quoteItem Mage_Sales_Model_Entity_Quote */
        $productIdAttr = $quoteItem->getAttribute('product_id');
        /* @var $productIdAttr Mage_Eav_Model_Entity_Attribute_Abstract */
        $productIdAttrId = $productIdAttr->getAttributeId();
        $productIdTableName = $productIdAttr->getBackend()->getTable();
        $productIdFieldName = $productIdAttr->getBackend()->isStatic() ? 'product_id' : 'value';

        $quote = Mage::getResourceSingleton('sales/quote');
        /* @var $quote Mage_Sales_Model_Entity_Quote */
        $isActiveAtrr = $quote->getAttribute('is_active');
        /* @var $attrIsActive Mage_Eav_Model_Entity_Attribute_Abstract */
        $isActiveTableName = $isActiveAtrr->getBackend()->getTable();
        $isActiveFieldName = $isActiveAtrr->getBackend()->isStatic() ? 'is_active' : 'value';

        $countSelect->from(array("quote_items" => $productIdTableName), "count(*)")
            ->join(array('quotes' => $isActiveTableName),
                'quotes.entity_id = quote_items.parent_id AND quotes.is_active = 1',
                array())
            ->where("quote_items.product_id = e.entity_id");

        $this->getSelect()
            ->from("", array("carts" => "({$countSelect})"))
            ->group("e.{$this->getProductEntityId()}");

        return $this;
    }

    public function addOrdersCount($from = '', $to = '')
    {
        $orderItem = Mage::getResourceSingleton('sales/order_item');
        /* @var $orderItem Mage_Sales_Model_Entity_Quote */
        $attr = $orderItem->getAttribute('product_id');
        /* @var $attr Mage_Eav_Model_Entity_Attribute_Abstract */
        $attrId = $attr->getAttributeId();
        $tableName = $attr->getBackend()->getTable();
        $fieldName = $attr->getBackend()->isStatic() ? 'product_id' : 'value';

        $this->getSelect()
            ->joinLeft(array("order_items" => $tableName),
                "order_items.{$fieldName} = e.{$this->getProductEntityId()} and order_items.attribute_id = {$attrId}", array())
            ->from("", array("orders" => "count(`order_items2`.entity_id)"))
            ->group("e.{$this->getProductEntityId()}");

        if ($from != '' && $to != '') {
            $dateFilter = " and order_items2.created_at BETWEEN '{$from}' AND '{$to}'";
        } else {
            $dateFilter = '';
        }

        $this->getSelect()
            ->joinLeft(array("order_items2" => $orderItem->getEntityTable()),
                "order_items2.entity_id = order_items.entity_id".$dateFilter, array());

        return $this;
    }

    public function addOrderedQty($from = '', $to = '')
    {
        $orderItem = Mage::getResourceSingleton('sales/order_item');
        /* @var $orderItem Mage_Sales_Model_Entity_Quote */
        $qtyOrderedAttr = $orderItem->getAttribute('qty_ordered');
        /* @var $qtyOrderedAttr Mage_Eav_Model_Entity_Attribute_Abstract */
        $qtyOrderedAttrId = $qtyOrderedAttr->getAttributeId();
        $qtyOrderedTableName = $qtyOrderedAttr->getBackend()->getTable();
        $qtyOrderedFieldName = $qtyOrderedAttr->getBackend()->isStatic() ? 'qty_ordered' : 'value';

        $productIdAttr = $orderItem->getAttribute('product_id');
        /* @var $productIdAttr Mage_Eav_Model_Entity_Attribute_Abstract */
        $productIdAttrId = $productIdAttr->getAttributeId();
        $productIdTableName = $productIdAttr->getBackend()->getTable();
        $productIdFieldName = $productIdAttr->getBackend()->isStatic() ? 'product_id' : 'value';

        if ($from != '' && $to != '') {
            $dateFilter = " AND `order`.created_at BETWEEN '{$from}' AND '{$to}'";
        } else {
            $dateFilter = "";
        }

        $this->getSelect()->reset()
            ->from(
                array('order_items2' => $qtyOrderedTableName),
                array('ordered_qty' => "sum(order_items2.{$qtyOrderedFieldName})"))
            ->joinInner(
                array('order_items' => $productIdTableName),
                "order_items.entity_id = order_items2.entity_id and order_items.attribute_id = {$productIdAttrId}",
                array())
            ->joinInner(array('e' => $this->getProductEntityTableName()),
                "e.entity_id = order_items.{$productIdFieldName} AND e.entity_type_id = {$this->getProductEntityTypeId()}")
            ->joinInner(array('order' => $this->getTable('sales/order_entity')),
                "order.entity_id = order_items.entity_id".$dateFilter, array())
            ->where("order_items2.attribute_id = {$qtyOrderedAttrId}")
            ->group('e.entity_id')
            ->having('ordered_qty > 0');

        return $this;
    }

    public function setOrder($attribute, $dir='desc')
    {
        switch ($attribute)
        {
            case 'carts':
            case 'orders':
            case 'ordered_qty':
                $this->getSelect()->order($attribute . ' ' . $dir);
                break;
            default:
                parent::setOrder($attribute, $dir);
        }

        return $this;
    }

    public function addViewsCount($from = '', $to = '')
    {
        /**
         * Getting event type id for catalog_product_view event
         */
        foreach (Mage::getModel('reports/event_type')->getCollection() as $eventType) {
            if ($eventType->getEventName() == 'catalog_product_view') {
                $productViewEvent = $eventType->getId();
                break;
            }
        }

        $this->getSelect()->reset()
            ->from(
                array('_table_views' => $this->getTable('reports/event')),
                array('views' => 'COUNT(_table_views.event_id)'))
            ->join(array('e' => $this->getProductEntityTableName()),
                "e.entity_id = _table_views.object_id AND e.entity_type_id = {$this->getProductEntityTypeId()}")
            ->where('_table_views.event_type_id = ?', $productViewEvent)
            ->group('e.entity_id')
            ->order('views desc')
            ->having('views > 0');

        if ($from != '' && $to != '') {
            $this->getSelect()
                ->where('logged_at >= ?', $from)
                ->where('logged_at <= ?', $to);
        }

        return $this;
    }
}