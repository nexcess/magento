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
 * Checkout item helper
 *
 * @category   Mage
 * @package    Mage_Checkout
 */
class Mage_Checkout_Block_Item extends Mage_Core_Block_Template
{
    public function getItemUrl($item)
    {
        if ($superProduct = $item->getSuperProduct()) {
            return $superProduct->getProductUrl();
        }
        
        if ($product = $item->getProduct()) {
            return $product->getProductUrl();
        }
        return '';
    }
    
    public function getItemImageUrl($item)
    {
        if ($superProduct = $item->getSuperProduct()) {
            return $superProduct->getThumbnailUrl();
        }
        
        if ($product = $item->getProduct()) {
            return $product->getThumbnailUrl();
        }
        return '';
    }
    
    public function getItemName($item)
    {
        $superProduct = $item->getSuperProduct();
        if ($superProduct && $superProduct->isConfigurable()) {
            return $superProduct->getName();
        }
        
        if ($product = $item->getProduct()) {
            return $product->getName();
        }
        return $item->getName();
    }
    
    public function getItemDescription($item)
    {
        if ($superProduct = $item->getSuperProduct()) {
            if ($superProduct->isConfigurable()) {
                return $this->_getConfigurableProductDescription($item->getProduct());
            }
        }
        return '';
    }
    
    public function getItemQty($item)
    {
        $qty = $item->getQty();
        if ($product = $item->getProduct()) {
            if ($product->getQtyIsDecimal()) {
                return number_format($qty, 2, null, '');
            }
        }
        return number_format($qty, 0, null, '');
    }
    
    public function getItemIsInStock($item)
    {
        if ($item->getProduct()->isSaleable()) {
            if ($item->getProduct()->getQty()>=$item->getQty()) {
                return true;
            }
        }
        return false;
    }
    
    protected function _getConfigurableProductDescription($product)
    {
 		$html = '<ul class="super-product-attributes">';
 		foreach ($product->getSuperProduct()->getSuperAttributes(true) as $attribute) {
 			$html.= '<li><strong>' . $attribute->getFrontend()->getLabel() . ':</strong> ';
 			if($attribute->getSourceModel()) {
 				$html.= htmlspecialchars($attribute->getSource()->getOptionText($product->getData($attribute->getAttributeCode())));
 			} else {
 				$html.= htmlspecialchars($product->getData($attribute->getAttributeCode()));
 			}
 			$html.='</li>';
 		}
 		$html.='</ul>';
 		return $html;
    }
}
