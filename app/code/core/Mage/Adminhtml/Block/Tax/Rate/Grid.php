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
class Mage_Adminhtml_Block_Tax_Rate_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setSaveParametersInSession(true);
        $this->setDefaultSort('region_name');
        $this->setDefaultDir('asc');
    }

    protected function _prepareCollection()
    {
        $rateCollection = Mage::getModel('tax/rate')->getCollection()
            ->joinTypeData()
            ->joinRegionTable();

        $this->setCollection($rateCollection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('tax_country_id',
            array(
                'header'=>Mage::helper('tax')->__('Country'),
                'type'  =>'country',
                'align' =>'left',
                'index' => 'tax_country_id',
            )
        );

        $this->addColumn('region_name',
            array(
                'header'=>Mage::helper('tax')->__('State/Region'),
                'align' =>'left',
                'index' => 'region_name',
                'filter_index' => 'code',
                'default' => '*',
            )
        );
/*
        $this->addColumn('county_name',
            array(
                'header'        =>Mage::helper('tax')->__('County'),
                'align'         =>'left',
                'index'         => 'county_name',
                'filter_index'  => 'county',
                'sortable'      => false,
                'filter'        => false,
                'default'       => '*',
            )
        );
*/
        $this->addColumn('tax_postcode',
            array(
                'header'=>Mage::helper('tax')->__('Zip/Post Code'),
                'align' =>'left',
                'index' => 'tax_postcode',
                'default' => '*',
            )
        );

        $rateTypeCollection = Mage::getModel('tax/rate_type')->getCollection()->load();

        foreach ($rateTypeCollection as $type) {
            $this->addColumn("tax_value_{$type->getTypeId()}",
                array(
                    'header'=>$type->getTypeName(),
                    'align' =>'right',
                    'filter' => false,
                    'index' => "rate_value_{$type->getTypeId()}",
                    'default' => '0.00',
                    'renderer' => 'adminhtml/tax_rate_grid_renderer_data', // Mage_Adminhtml_Block_Tax_Rate_Grid_Renderer_Data
                )
            );
        }

        $this->addExportType('*/*/exportCsv', Mage::helper('tax')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('tax')->__('XML'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('rate' => $row->getTaxRateId()));
    }

}

