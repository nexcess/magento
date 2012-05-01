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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product view abstract block
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
abstract class Mage_Catalog_Block_Product_View_Abstract extends Mage_Core_Block_Template
{
    /**
     * Retrieve currently viewed product object
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    public function getTierPrices()
    {
        $product = $this->getProduct();
        $prices  = $product->getFormatedTierPrice();
        $res = array();
        if (is_array($prices)) {
            foreach ($prices as $price) {
                $price['price_qty'] = $price['price_qty']*1;
                if ($product->getPrice() != $product->getFinalPrice()) {
                    if ($price['price']<$product->getFinalPrice()) {
                        $price['savePercent'] = ceil(100 - (( 100/$product->getFinalPrice() ) * $price['price'] ));
                        $res[] = $price;
                    }
                }
                else {
                    if ($price['price']<$product->getPrice()) {
                        $price['savePercent'] = ceil(100 - (( 100/$product->getPrice() ) * $price['price'] ));
                        $res[] = $price;
                    }
                }
            }
        }
        return $res;
    }
}
