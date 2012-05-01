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


/**
 * Adminhtml catalog product edit action attributes update tabs block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Edit_Action_Attribute_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('attributes_update_tabs');
        $this->setDestElementId('attributes_edit_form');
        $this->setTitle(Mage::helper('catalog')->__('Products Information'));
    }

    protected function _prepareLayout()
    {
        $this->addTab('attributes', array(
            'label'     => Mage::helper('catalog')->__('Attributes'),
            'content'   => $this->getLayout()->createBlock(
                                'adminhtml/catalog_product_edit_action_attribute_tab_attributes'
                           )->toHtml(),
        ));

        $this->addTab('inventory', array(
            'label'     => Mage::helper('catalog')->__('Inventory'),
            'content'   => $this->getLayout()->createBlock(
                                'adminhtml/catalog_product_edit_action_attribute_tab_inventory'
                           )->toHtml(),
        ));
    }

}
