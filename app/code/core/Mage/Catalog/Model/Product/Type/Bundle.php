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
 * Bundle product type implementation
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Product_Type_Bundle extends Mage_Catalog_Model_Product_Type_Abstract
{
/*  from product resource model
    public function _saveBundle($product)
    {
    	if(!$product->isBundle()) {
    		return $this;
    	}

    	$options = $product->getBundleOptions();

    	if(!is_array($options)) { // If data copied from other store
    		$optionsCollection = $this->getBundleOptionCollection($product, true)
    			->load();
    		$options = $optionsCollection->toArray();
    	} else {
    		$optionsCollection = $this->getBundleOptionCollection($product)
    			->load();
    	}

    	$optionIds = array();

    	foreach($options as $option) {
    		if($option['id'] && $optionObject = $optionsCollection->getItemById($option['id'])) {
    			$optionObject
    				->setStoreId($product->getStoreId());
    			$optionIds[] = $optionObject->getId();
    		} else {
    			$optionObject = $optionsCollection->getItemModel()
    				->setProductId($product->getId())
    				->setStoreId($product->getStoreId());
    		}

    		$optionObject->setLabel($option['label']);
    		$optionObject->setPosition($option['position']);

    		$optionObject->save();

    		if(!isset($option['products'])) {
    			$links = array();
    			$linksIds = array();
    			if(isset($option['links']) && is_array($option['links'])) {
    				$links = $option['links'];
    				$linksIds = array_keys($option['links']);
    			}

    			foreach ($links as $productId=>$link) {
    				if(!$linkObject=$optionObject->getLinkCollection()->getItemByColumnValue('product_id', $productId)) {
    					$linkObject = clone $optionObject->getLinkCollection()->getObject();
    				}

    				$linkObject
    					->addData($link)
    					->setOptionId($optionObject->getId())
    					->setProductId($productId);
    				$linkObject->save();
    			}

    			foreach ($optionObject->getLinkCollection() as $linkObject) {
    				if(!in_array($linkObject->getProductId(),$linksIds)) {
    					$linkObject->delete();
    				}
    			}
    		}
    	}

    	foreach ($optionsCollection as $optionObject) {
    		if(!in_array($optionObject->getId(),$optionIds)) {
				$optionObject->delete();
			}
    	}

    	return $this;
    }

    public function getBundleOptionCollection($product, $useBaseStoreId=false)
    {
    	$collection = Mage::getModel('catalog/product_bundle_option')->getResourceCollection()
    			->setProductIdFilter($product->getId());

    	if($useBaseStoreId) {
    		$collection->setStoreId($product->getBaseStoreId());
    	} else {
    		$collection->setStoreId($product->getStoreId());
    	}

    	return $collection;
    }

*/
}
