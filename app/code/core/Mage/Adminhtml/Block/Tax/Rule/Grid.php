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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Mage_Adminhtml_Block_Tax_Rule_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('tax_rule_id');
        $this->setId('taxRuleGrid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('tax/rule')
            ->getCollection()
            ->joinClassTable()
            ->joinRateTypeTable();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('tax_rule_id',
            array(
                'header'=>Mage::helper('tax')->__('ID'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'tax_rule_id'
            )
        );

        $this->addColumn('customer_class_name',
            array(
                'header'=>Mage::helper('tax')->__('Customer Tax Class'),
                'align' =>'left',
                'index' => 'class_customer_name',
                'filter_index' => 'class_customer_name',
            )
        );

        $this->addColumn('product_class_name',
            array(
                'header'=>Mage::helper('tax')->__('Product Tax Class'),
                'align' =>'left',
                'index' => 'class_product_name',
                'filter_index' => 'class_product_name',
            )
        );

        $this->addColumn('type_name',
            array(
                'header'=>Mage::helper('tax')->__('Tax Rate'),
                'align' =>'left',
                'index' => 'rate_type_name'
            )
        );

        $actionsUrl = $this->getUrl('*/*/');

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('rule' => $row->getTaxRuleId()));
    }

}
