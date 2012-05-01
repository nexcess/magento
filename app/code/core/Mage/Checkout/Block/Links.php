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
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Links block
 *
 * @category   Mage
 * @package    Mage_Checkout
 */

class Mage_Checkout_Block_Links extends Mage_Core_Block_Template
{
    public function addCartLink()
    {
        $count = $this->helper('checkout/cart')->getSummaryCount();

        if( $count == 1 ) {
            $text = $this->__('My Cart (%s item)', $count);
        } elseif( $count > 0 ) {
            $text = $this->__('My Cart (%s items)', $count);
        } else {
            $text = $this->__('My Cart');
        }

        $this->getParentBlock()->addLink($text, 'checkout/cart', $text, true, array(), 50, null, 'class="top-link-cart"');
    }

    public function addCheckoutLink()
    {
        $text = Mage::helper('checkout')->__('Checkout');
        $this->getParentBlock()->addLink($text, 'checkout', $text, true, array(), 60, null, 'class="top-link-checkout"');
    }
}