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
 * Review controller
 *
 * @category   Mage
 * @package    Mage_Review
 */
class Mage_Review_ProductController extends Mage_Core_Controller_Front_Action
{
	protected function _initProduct()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        if (!$productId) {
            return false;
        }

        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            return false;
        }
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            Mage::register('current_category', $category);
        }
        Mage::register('current_product', $product);
        Mage::register('product', $product);
        return $product;
    }

    public function postAction()
    {
        $productId  = $this->getRequest()->getParam('id', false);
        $data       = $this->getRequest()->getPost();
        $arrRatingId= $this->getRequest()->getParam('ratings', array());

        if ($productId && !empty($data)) {
            $session    = Mage::getSingleton('review/session');
            $review     = Mage::getModel('review/review')->setData($data);
            $validateRes= $review->validate();

            if (true === $validateRes) {
                try {
                    $review->setEntityId(Mage_Review_Model_Review::ENTITY_PRODUCT)
                        ->setEntityPkValue($productId)
                        ->setStatusId(Mage_Review_Model_Review::STATUS_PENDING)
                        ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->setStores(array(Mage::app()->getStore()->getId()))
                        ->save();

                    foreach ($arrRatingId as $ratingId=>$optionId) {
                    	Mage::getModel('rating/rating')
                    	   ->setRatingId($ratingId)
                    	   ->setReviewId($review->getId())
                    	   ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
                    	   ->addOptionVote($optionId, $productId);
                    }

                    $review->aggregate();
                    $session->addSuccess($this->__('Your review has been accepted for moderation'));
                }
                catch (Exception $e){
                    $session->setFormData($data);
                    $session->addError($this->__('Unable to post review. Please, try again later.'));
                }
            }
            else {
                $session->setFormData($data);
                if (is_array($validateRes)) {
                    foreach ($validateRes as $errorMessage) {
                        $session->addError($errorMessage);
                    }
                }
                else {
                    $session->addError($this->__('Unable to post review. Please, try again later.'));
                }
            }
        }

        $this->_redirectReferer();
    }

    public function listAction()
    {
        if ($product = $this->_initProduct()) {
            Mage::register('productId', $product->getId());
            $this->loadLayout();
            $this->_initLayoutMessages('review/session');
            $this->renderLayout();
        } else {
            $this->_forward('noRoute');
        }
    }

    public function viewAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('review/session');
        $this->renderLayout();
    }
}