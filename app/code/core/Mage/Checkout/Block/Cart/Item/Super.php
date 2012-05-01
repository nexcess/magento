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
 * Checkout item super product options block
 *
 * @category   Mage
 * @package    Mage_Checkout
 */
 class Mage_Checkout_Block_Cart_Item_Super extends Mage_Core_Block_Abstract
 {
     protected $_product = null;
     public function setProduct($product)
     {
        $this->_product = $product;
        return $this;
     }

     public function getProduct()
     {
         return $this->_product;
     }

     protected function _toHtml()
     {
        if (!$this->_beforeToHtml()) {
            return '';
        }
         $result = '<ul class="super-product-attributes">';
         foreach ($this->getProduct()->getSuperProduct()->getSuperAttributes(true) as $attribute) {
             $result.= '<li><strong>' . $attribute->getFrontend()->getLabel() . ':</strong> ';
             if($attribute->getSourceModel()) {
                 $result.= htmlspecialchars(
                    $attribute->getSource()->getOptionText($this->getProduct()->getData($attribute->getAttributeCode()))
                );
             } else {
                 $result.= htmlspecialchars($this->getProduct()->getData($attribute->getAttributeCode()));
             }
             $result.='</li>';
         }
         $result.='</ul>';
         return $result;
     }

 }
