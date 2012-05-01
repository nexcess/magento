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
 * Product bundle options form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Bundle_Option extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_parent = null;

    public function setParent(Mage_Core_Block_Abstract $parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    public function getParent()
    {
        return $this->_parent;
    }


    protected function _prepareForm()
    {
    	$form = new Varien_Data_Form();

    	$fieldset = $form->addFieldset('fieldset', array(
    		'legend' => Mage::helper('catalog')->__('Options')
    	));

    	$fieldset->addField('options', 'text',
    		array(
    			'name'=>'b_option',
    			'class'=>'required-entry'
    		)
    	);


    	$form->getElement('options')->setRenderer(
    		$this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_bundle_option_renderer')
    			->setParent($this)
      	);

    	$this->setForm($form);
    	return $this;
    }

}

