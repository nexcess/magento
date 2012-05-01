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
 * admin customer left menu
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('customer_info_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('customer')->__('Customer Information'));
    }

    protected function _beforeToHtml()
    {
        if (Mage::registry('current_customer')->getId()) {
            $this->addTab('view', array(
                'label'     => Mage::helper('customer')->__('Customer View'),
                'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_view')->toHtml(),
                'active'    => true
            ));
        }

        $this->addTab('account', array(
            'label'     => Mage::helper('customer')->__('Account Information'),
            'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_account')->initForm()->toHtml(),
            'active'    => Mage::registry('current_customer')->getId() ? false : true
        ));

        $this->addTab('addresses', array(
            'label'     => Mage::helper('customer')->__('Addresses'),
            'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_addresses')->initForm()->toHtml(),
        ));

        if (Mage::registry('current_customer')->getId()) {
             $this->addTab('orders', array(
                 'label'     => Mage::helper('customer')->__('Orders'),
                 'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_orders')->toHtml(),
             ));

            $carts = '';
            if (Mage::registry('current_customer')->getSharingConfig()->isWebsiteScope()) {
                $website = Mage::app()->getWebsite(Mage::registry('current_customer')->getWebsiteId());
                $blockName = 'customer_cart_'.$website->getId();
                $carts .= $this->getLayout()->createBlock('adminhtml/customer_edit_tab_cart', $blockName, array('website_id' => $website->getId()))
                        ->toHtml();
            } else {
                foreach (Mage::app()->getWebsites() as $website) {
                    if (count($website->getStoreIds()) > 0) {
                        $blockName = 'customer_cart_'.$website->getId();
                        $carts .= $this->getLayout()->createBlock('adminhtml/customer_edit_tab_cart', $blockName, array('website_id' => $website->getId()))
                            ->setWebsiteId($website->getId())
                            ->setCartHeader($this->__('Shopping Cart from %s', $website->getName()))
                            ->toHtml();
                    }
                }
            }

            $this->addTab('cart', array(
                'label'     => Mage::helper('customer')->__('Shopping Cart'),
                'content'   => $carts,
            ));

            $this->addTab('wishlist', array(
                'label'     => Mage::helper('customer')->__('Wishlist'),
                'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_wishlist')->toHtml(),
            ));

            $this->addTab('newsletter', array(
                'label'     => Mage::helper('customer')->__('Newsletter'),
                'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_newsletter')->initForm()->toHtml()
            ));

            $this->addTab('reviews', array(
                'label'     => Mage::helper('customer')->__('Product Reviews'),
                'content'   => $this->getLayout()->createBlock('adminhtml/review_grid', 'admin.customer.reviews')
                        ->setCustomerId(Mage::registry('current_customer')->getId())
                        ->setUseAjax(true)
                        ->toHtml(),
            ));

            $this->addTab('tags', array(
                'label'     => Mage::helper('customer')->__('Product Tags'),
                'content'   => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_tag', 'admin.customer.tags')
                        ->setCustomerId(Mage::registry('current_customer')->getId())
                        ->setUseAjax(true)
                        ->toHtml(),
            ));
        }
        Varien_Profiler::stop('customer/tabs');
        return parent::_beforeToHtml();
    }

}
