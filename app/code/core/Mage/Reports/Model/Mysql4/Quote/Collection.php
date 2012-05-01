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
 * Reports quote collection
 *
 * @category   Mage
 * @package    Mage_Reports
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Reports_Model_Mysql4_Quote_Collection extends Mage_Sales_Model_Mysql4_Quote_Collection
{

    public function prepareForAbandonedReport($storeIds)
    {
        $this->addFieldToFilter('items_count', array('neq' => '0'))
            ->addFieldToFilter('main_table.is_active', '1')
            ->addSubtotal($storeIds)
            ->addCustomerData()
            ->setOrder('updated_at');
        if (is_array($storeIds)) {
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));
        }
        return $this;
    }

    public function addCustomerData()
    {
        $customerEntity = Mage::getResourceSingleton('customer/customer');
        $attrFirstname = $customerEntity->getAttribute('firstname');
        $attrFirstnameId = $attrFirstname->getAttributeId();
        $attrFirstnameTableName = $attrFirstname->getBackend()->getTable();

        $attrLastname = $customerEntity->getAttribute('lastname');
        $attrLastnameId = $attrLastname->getAttributeId();
        $attrLastnameTableName = $attrLastname->getBackend()->getTable();

        $attrEmail = $customerEntity->getAttribute('email');
        $attrEmailTableName = $attrEmail->getBackend()->getTable();

        $this->getSelect()
            ->joinInner(
                array('cust_email'=>$attrEmailTableName),
                'cust_email.entity_id=main_table.customer_id',
                array('email'=>'cust_email.email')
            )
            ->joinInner(
                array('cust_fname'=>$attrFirstnameTableName),
                'cust_fname.entity_id=main_table.customer_id and cust_fname.attribute_id='.$attrFirstnameId,
                array('firstname'=>'cust_fname.value')
            )
            ->joinInner(
                array('cust_lname'=>$attrLastnameTableName),
                'cust_lname.entity_id=main_table.customer_id and cust_lname.attribute_id='.$attrFirstnameId,
                array(
                    'lastname'=>'cust_lname.value',
                    'customer_name' => new Zend_Db_Expr('CONCAT(cust_fname.value, " ", cust_lname.value)')
                )
            );

        return $this;
    }

    public function addSubtotal($storeIds = '')
    {
        $this->getSelect()
            ->joinInner(array('quote_addr' => $this->getTable('sales/quote_address')),
                "quote_addr.quote_id=main_table.entity_id",
                array())
            ->joinInner(array('quote_addr_subtotal' => $this->getTable('sales/quote_address')),
                "quote_addr_subtotal.quote_id=quote_addr.quote_id",
                 array());
        if ($storeIds == '') {
            $this->getSelect()->from("", array("subtotal" => "SUM(IFNULL(quote_addr_subtotal.base_subtotal_with_discount/main_table.store_to_base_rate, 0))"));
        } else {
            $this->getSelect()->from("", array("subtotal" => "SUM(IFNULL(quote_addr_subtotal.base_subtotal_with_discount, 0))"));
        }
        $this->getSelect()->group('main_table.entity_id');

        return $this;
    }

    public function getSelectCountSql()
    {
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->from("", "count(DISTINCT main_table.entity_id)");
        $sql = $countSelect->__toString();
        return $sql;
    }
}
