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
 * @package    Mage_Tax
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Tax_Model_Mysql4_Rate_Data extends Mage_Core_Model_Mysql4_Abstract
{

    protected function _construct()
    {
        $this->_init('tax/tax_rate_data', 'tax_rate_data_id');
    }

    public function fetchRate(Mage_Tax_Model_Rate_Data $request)
    {
        // initialize select from rate_data
        $select = $this->_getReadAdapter()->select();
        // get maximum rate from found
        $select->from(array('data'=>$this->getMainTable()), array('value'=>'max(rate_value)'));

        if ($request->getRateTypeId()) {
            $select->where('data.rate_type_id=?', $request->getRateTypeId());
        }

        // join rule table with conditions
        if ($request->getCustomerClassId() && $request->getProductClassId()) {
            $select->join(array('rule'=>$this->getTable('tax_rule')), 'rule.tax_rate_type_id=data.rate_type_id', array());
            $select->where('rule.tax_customer_class_id=?', $request->getCustomerClassId());
            $select->where('rule.tax_product_class_id=?', $request->getProductClassId());
        }


        // join rate table with conditions
        $select->join(array('rate'=>$this->getTable('tax_rate')), 'rate.tax_rate_id=data.tax_rate_id', array());
        $select->where('rate.tax_country_id=:country_id');
        $select->where('rate.tax_region_id is null or rate.tax_region_id=0 or rate.tax_region_id=:region_id');
        $select->where("rate.tax_postcode is null or rate.tax_postcode in ('','*') or rate.tax_postcode=:tax_postcode");
        // for future county handling
//        if ($request->getCountyId()) {
//            // TODO: make it play nice with zip
//            $select->where('rate.tax_county_id is null or rate.tax_county_id=?', $request->getCountyId());
//        }
        $select->order('tax_region_id desc')->order('tax_postcode desc');

        $bind = array(
            'country_id'    => $request->getCountryId(),
            'region_id'     => $request->getRegionId(),
            'tax_postcode'  => $request->getPostcode()
        );
        $rows = $this->_getReadAdapter()->fetchAll($select, $bind);
        return $rows ? $rows[0]['value'] : 0;
    }
}