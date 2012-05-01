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
 * @package    Mage_Review
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product review helper
 *
 */
class Mage_Review_Helper_Product extends Mage_Core_Helper_Url
{
    /**
     * Check summary calculation for review
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Review_Helper_Product
     */
    protected function _checkProductSummary($product)
    {
        if( !$product->getRatingSummary() ) {
	        Mage::getModel('review/review')->getEntitySummary($product, Mage::app()->getStore()->getId());
	    }
	    return $this;
    }

    /**
     * Retrieve product review count
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  int
     */
    public function getReviewCount($product)
    {
        $this->_checkProductSummary($product);
        return $product->getRatingSummary()->getReviewsCount();
    }

    /**
     * Retrieve product summary rating
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  float
     */
    public function getSummaryRating($product)
    {
        $this->_checkProductSummary($product);
        return $product->getRatingSummary()->getRatingSummary();
    }

    /**
     * Retrieve product review summary html block
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   string $type
     * @param   bool $displayBlock
     * @return  string
     */
    public function getSummaryHtml($product, $type=null, $displayBlock=false)
    {
        if ($type == 'short') {
            return $this->getShortSummaryHtml($product, $displayBlock);
        }
        return $this->getFullSummaryHtml($product, $displayBlock);
    }

    /**
     * Retrieve url for new review creation
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  string
     */
    public function getNewUrl($product)
    {
        $params = array('id' => $product->getId());
        if ($product->getCategoryId()) {
            $params['category'] = $product->getCategoryId();
        }
        return $this->_getUrl('review/product/list', $params);
    }

    /**
     * Retrieve url of product review list
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  string
     */
    public function getListUrl($product)
    {
        return $this->getNewUrl($product);
    }

    /**
     * Retrieve short block of product summary review
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   bool $displayBlock
     * @return  string
     */
    public function getShortSummaryHtml($product, $displayBlock=false)
    {
        $html = '';
        if ($this->getReviewCount($product) && $this->getSummaryRating($product)) {
            $html = '<div class="ratings">
                <div class="rating-box">
                    <div class="rating" style="width:'.$this->getSummaryRating($product).'%;"></div>
                </div>
                ( '.$this->getReviewCount($product).' )
            </div>';
        } elseif($this->getReviewCount($product)) {
            $html = '<div class="ratings">
                <a href="'.$this->getListUrl($product).'">
                    <small class="count">'.$this->__('%d Review(s)', $this->getReviewCount($product)).'</small>
                </a>
            </div>';
        } else {
            if ($displayBlock) {
                $html = '<p>
                    <a href="'.$this->getNewUrl($product).'#review-form">
                    '.$this->__('Be the first to review this product').'
                    </a>
                </p>';
            }
        }
        return $html;
    }

    /**
     * Retrieve full block of product summary review
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   bool $displayBlock
     * @return  string
     */
    public function getFullSummaryHtml($product, $displayBlock=false)
    {
        $html = '';

        if ($this->getReviewCount($product) && $this->getSummaryRating($product)) {
            $html.= '<div class="ratings">
                <div class="rating-box">
                    <div class="rating" style="width:'.$this->getSummaryRating($product).'%"></div>
                </div>
                <div class="clear"></div>
                <a href="'.$this->getListUrl($product).'">
                    <small class="count">'.$this->__('%d Review(s)', $this->getReviewCount($product)).'</small>
                </a><br/>
                <a href="'.$this->getNewUrl($product).'#review-form">
                    <small>'.$this->__('Add Your Review').'</small>
                </a>
            </div>';
        } elseif($this->getReviewCount($product)) {
            $html.= '<div class="ratings">
                <div class="clear"></div>
                <a href="'.$this->getListUrl($product).'">
                    <small class="count">'.$this->__('%d Review(s)', $this->getReviewCount($product)).'</small>
                </a><br/>
                <a href="'.$this->getNewUrl($product).'#review-form">
                    <small>'.$this->__('Add Your Review').'</small>
                </a>
            </div>';
        } else {
            if ($displayBlock) {
                $html = '<p>
                    <a href="'.$this->getNewUrl($product).'#review-form">
                    '.$this->__('Be the first to review this product').'
                    </a>
                </p>';
            }
        }

        return $html;
    }
}
