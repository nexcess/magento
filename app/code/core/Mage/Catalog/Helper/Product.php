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
 * Catalog category helper
 *
 */
class Mage_Catalog_Helper_Product extends Mage_Core_Helper_Url
{
    protected $_statuses;

    protected $_priceBlock;

    /**
     * Retrieve product view page url
     *
     * @param   mixed $product
     * @return  string
     */
    public function getProductUrl($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $urlKey = $product->getUrlKey() ? $product->getUrlKey() : $product->getName();
            $params = array(
                's'         => $this->_prepareString($urlKey),
                'id'        => $product->getId(),
                'category'  => $product->getCategoryId()
            );
            return $this->_getUrl('catalog/product/view', $params);
        }
        if ((int) $product) {
            return $this->_getUrl('catalog/product/view', array('id'=>$product));
        }
        return false;
    }

    /**
     * Retrieve product price
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  float
     */
    public function getPrice($product)
    {
        return $product->getPrice();
    }

    /**
     * Retrieve product final price
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  float
     */
    public function getFinalPrice($product)
    {
        return $product->getFinalPrice();
    }

    /**
     * Retrieve base image url
     *
     * @return string
     */
    public function getImageUrl($product)
    {
        $url = false;
        if (!$product->getImage()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('image')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

    /**
     * Retrieve small image url
     *
     * @return unknown
     */
    public function getSmallImageUrl($product)
    {
        $url = false;
        if (!$product->getSmallImage()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('small_image')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

    /**
     * Retrieve thumbnail image url
     *
     * @return unknown
     */
    public function getThumbnailUrl($product)
    {
        return '';
    }

    public function getEmailToFriendUrl($product)
    {
        return $this->_getUrl('sendfriend/product/send', array('id'=>$product->getId()));
    }

    /**
     * Retrieve product price html block
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   bool $displayMinimalPrice
     * @return  string
     */
    public function getPriceHtml($product, $displayMinimalPrice = false)
    {
        if (is_null($this->_priceBlock)) {
            $className = Mage::getConfig()->getBlockClassName('core/template');
            $block = new $className();
            $block->setType('core/template')
                ->setIsAnonymous(true)
                ->setTemplate('catalog/product/price.phtml');
            // TODO make nice block name to be able to set template form the layout
            $this->_priceBlock = $block;
        }
        $html = '';

        $this->_priceBlock->setProduct($product);
        $this->_priceBlock->setDisplayMinimalPrice($displayMinimalPrice);

        return $this->_priceBlock->toHtml();
    }

    public function getStatuses()
    {
        if(is_null($this->_statuses)) {
            $this->_statuses = array();//Mage::getModel('catalog/product_status')->getResourceCollection()->load();
        }

        return $this->_statuses;
    }

    /**
     * Retrieve product description
     *
     * @param   Mage_Catalog_Model_Product $item
     * @return  string
     */
    public function getProductDescription($product)
    {
        if ($superProduct = $product->getSuperProduct()) {
            if ($superProduct->isConfigurable()) {
                return $this->_getConfigurableProductDescription($product->getProduct());
            }
        }
        return '';
    }

    protected function _getConfigurableProductDescription($product)
    {
 		$html = '<ul class="super-product-attributes">';
 		$attributes = $product->getSuperProduct()->getTypeInstance()->getUsedProductAttributes();
 		foreach ($attributes as $attribute) {
 			$html.= '<li><strong>' . $attribute->getFrontend()->getLabel() . ':</strong> ';
 			if($attribute->getSourceModel()) {
 				$html.= $this->htmlEscape(
 				   $attribute->getSource()->getOptionText($product->getData($attribute->getAttributeCode()))
                );
 			} else {
 				$html.= $this->htmlEscape($product->getData($attribute->getAttributeCode()));
 			}
 			$html.='</li>';
 		}
 		$html.='</ul>';
        return $html;
    }

    /**
     * Check if a product can be shown
     *
     * @param  Mage_Catalog_Model_Product|int $product
     * @return boolean
     */
    public function canShow($product, $where = 'catalog')
    {
        if (is_int($product)) {
            $product = Mage::getModel('catalog/product')->load($product);
        }

        /* @var $product Mage_Catalog_Model_Product */

        if (!$product->getId()) {
            return false;
        }

        return $product->isVisibleInCatalog();
        // TODO shold be check both status and visibility
        //if ('catalog' == $where) {
        //}

        return false;
    }
}
