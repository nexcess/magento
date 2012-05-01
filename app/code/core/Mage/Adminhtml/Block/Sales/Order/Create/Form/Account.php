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
 * Create order account form
 *
 */
class Mage_Adminhtml_Block_Sales_Order_Create_Form_Account extends Mage_Adminhtml_Block_Sales_Order_Create_Abstract
{
    protected $_form;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('sales/order/create/form/account.phtml');
    }

    protected function _prepareLayout()
    {
        Varien_Data_Form::setElementRenderer(
            $this->getLayout()->createBlock('adminhtml/widget_form_renderer_element')
        );
        Varien_Data_Form::setFieldsetRenderer(
            $this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset')
        );
        Varien_Data_Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset_element')
        );
    }

    public function getHeaderCssClass()
    {
        return 'head-account';
    }

    public function getHeaderText()
    {
        return Mage::helper('sales')->__('Account Information');
    }

    public function getForm()
    {
        $this->_prepareForm();
        return $this->_form;
    }

    protected function _prepareForm()
    {
        if (!$this->_form) {
            if ($this->getQuote()->getCustomerIsGuest()) {
                $display = array('email');
            }
            else {
                $display = array('email', 'group_id');
            }

            $this->_form = new Varien_Data_Form();
            $fieldset = $this->_form->addFieldset('main', array());
            $customerModel = Mage::getModel('customer/customer');

            foreach ($customerModel->getAttributes() as $attribute) {
                if (!in_array($attribute->getAttributeCode(), $display)) {
                    continue;
                }
                if ($inputType = $attribute->getFrontend()->getInputType()) {
                    $element = $fieldset->addField($attribute->getAttributeCode(), $inputType,
                        array(
                            'name'  => $attribute->getAttributeCode(),
                            'label' => $attribute->getFrontend()->getLabel(),
                        )
                    )
                    ->setEntityAttribute($attribute);

                    if ($inputType == 'select' || $inputType == 'multiselect') {
                        $element->setValues($attribute->getFrontend()->getSelectOptions());
                    }
                }
            }

            $this->_form->addFieldNameSuffix('order[account]');

            $this->_form->setValues($this->getCustomerData());
        }
        return $this;
    }

    public function getCustomerData()
    {
        $data = $this->getCustomer()->getData();
        foreach ($this->getQuote()->getData() as $key=>$value) {
        	if (strstr($key, 'customer_')) {
        	    $data[str_replace('customer_', '', $key)] = $value;
        	}
        }
        $data['group_id'] = $this->getCreateOrderModel()->getCustomerGroupId();
        return $data;
    }
}
