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
 * Product View block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @module     Catalog
 */
class Mage_Catalog_Block_Product_View extends Mage_Catalog_Block_Product_Abstract
{
    protected function _prepareLayout()
    {
        $this->getLayout()->createBlock('catalog/breadcrumbs');
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            if ($title = $this->getProduct()->getMetaTitle()) {
                $headBlock->setTitle($title.' '.Mage::getStoreConfig('catalog/seo/title_separator').' '.Mage::getStoreConfig('system/store/name'));
            }

            if ($keyword = $this->getProduct()->getMetaKeyword()) {
                $headBlock->setKeywords($keyword);
            } elseif( $currentCategory = Mage::registry('current_category') ) {
                $headBlock->setKeywords($this->getProduct()->getName());
            }

            if ($description = $this->getProduct()->getMetaDescription()) {
                $headBlock->setDescription( ($description) );
            } else {
                $headBlock->setDescription( $this->getProduct()->getDescription() );
            }
        }
        return parent::_prepareLayout();
    }

    /**
     * Retrieve current product model
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!Mage::registry('product') && $this->getProductId()) {
            $product = Mage::getModel('catalog/product')->load($this->getProductId());
            Mage::register('product', $product);
        }
        return Mage::registry('product');
    }

    public function getAdditionalData()
    {
        $data = array();
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined()) {

                $value = $attribute->getFrontend()->getValue($product);
                if (strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                       'label' => $attribute->getFrontend()->getLabel(),
                       'value' => $value//$product->getData($attribute->getAttributeCode())
                    );
                }
            }
        }
        return $data;
    }

    public function getAlertHtml($type)
    {
        return $this->getLayout()->createBlock('customeralert/alerts')
            ->setAlertType($type)
            ->toHtml();
    }

    public function getMinimalQty($product)
    {
        if ($stockItem = $product->getStockItem()) {
            return $stockItem->getMinSaleQty()>1 ? $stockItem->getMinSaleQty()*1 : null;
        }
        return null;
    }

    public function canEmailToFriend()
    {
        $sendToFriendModel = Mage::registry('send_to_friend_model');
        return $sendToFriendModel && $sendToFriendModel->canEmailToFriend();
    }

    public function getAddToCartUrl($product, $additional = array())
    {
        $additional = array();

        if ($this->getRequest()->getParam('wishlist_next')){
            $additional['wishlist_next'] = 1;
        }

        return parent::getAddToCartUrl($product, $additional);
    }

}
