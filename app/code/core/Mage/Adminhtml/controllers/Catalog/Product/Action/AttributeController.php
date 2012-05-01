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
 * Adminhtml catalog product action attribute update controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Catalog_Product_Action_AttributeController extends Mage_Adminhtml_Controller_Action
{

    protected function _construct()
    {
        // Define module dependent translate
        $this->setUsedModuleName('Mage_Catalog');
    }

    public function editAction()
    {
        if(!$this->_validateProducts()) {
            return;
        }
        /* OLD FUNCTINALITY
        if($countNotInStore = count($this->_getHelper()->getProductsNotInStoreIds())) {
            // If we have selected products, that not exists in selected store we'll show warning
            $this->_getSession()->addWarning(
                $this->__('There is %d product(s) that will be not updated for selected store', $countNotInStore)
            );
        } */

        $this->loadLayout();

        // Store switcher
        $this->_addLeft(
                $this->getLayout()->createBlock('adminhtml/store_switcher')
                    ->setDefaultStoreName($this->__('Default Values'))
                    ->setSwitchUrl($this->getUrl('*/*/*', array('_current'=>true, 'store'=>null)))
        );

        $this->_addLeft(
            $this->getLayout()->createBlock(
                'adminhtml/catalog_product_edit_action_attribute_tabs',
                'attributes_tabs'
            )
        );

        $this->_addContent(
            $this->getLayout()->createBlock('adminhtml/catalog_product_edit_action_attribute')
        );

        $this->renderLayout();
    }

    public function saveAction()
    {
        if(!$this->_validateProducts()) {
            return;
        }

        // Attributes values for massupdate
        $data = $this->getRequest()->getParam('attributes');

        if(!is_array($data)) {
            $data = array();
        }

        $productsNotInStore = $this->_getHelper()->getProductsNotInStoreIds();
        try {
            foreach ($this->_getHelper()->getProducts() as $product) {
                if(in_array($product->getId(), $productsNotInStore)) {
                    // If product not available in selected store
                    continue;
                }

                $product->setStoreId($this->_getHelper()->getSelectedStoreId())
                    ->load($product->getId())
                    ->addData($data);


                $product->save();
            }

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were successfully updated',
                count($this->_getHelper()->getProducts()))
            );
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError(
                $this->__('There was an error while updating product(s) attributes')
            );
        }

        $this->_redirect('*/catalog_product/', array('store'=>$this->_getHelper()->getSelectedStoreId()));
    }

    /**
     * Validate selection of products for massupdate
     *
     * @return boolean
     */
    protected function _validateProducts()
    {
        if(!is_array($this->_getHelper()->getProductIds())) {
            $this->_getSession()->addError($this->__('Please select products for attributes update'));
            $this->_redirect('*/catalog_product/', array('_current'=>true));
            return false;
        }

        return true;
    }

    /**
     * Rertive data manipulation helper
     *
     * @return Mage_Adminhtml_Helper_Catalog_Product_Edit_Action_Attribute
     */
    protected function _getHelper()
    {
        return Mage::helper('adminhtml/catalog_product_edit_action_attribute');
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/attributes/attributes');
    }
}