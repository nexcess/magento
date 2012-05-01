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
 * @package    Mage_Tag
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Tag model
 *
 * @category   Mage
 * @package    Mage_Tag
 */

class Mage_Tag_Model_Tag extends Mage_Core_Model_Abstract
{
    const STATUS_DISABLED = -1;
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;

    protected function _construct()
    {
        $this->_init('tag/tag');
    }

    public function loadByName($name)
    {
        $this->_getResource()->loadByName($this, $name);
        return $this;
    }

    public function aggregate()
    {
        $this->_getResource()->aggregate($this);
        return $this;
    }

    public function productEventAggregate($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $collection = $this->getResourceCollection()
            ->joinRel()
            ->addProductFilter($product->getId())
            ->addTagGroup()
            ->load();


        $collection->walk('aggregate');


        return $this;
    }

    public function addSummary($storeId)
    {
        $this->setStoreId($storeId);
        $this->_getResource()->addSummary($this);
        return $this;
    }

    public function getApprovedStatus()
    {
        return self::STATUS_APPROVED;
    }

    public function getPendingStatus()
    {
        return self::STATUS_PENDING;
    }

    public function getEntityCollection()
    {
        return Mage::getResourceModel('tag/product_collection');
    }

    public function getCustomerCollection()
    {
        return Mage::getResourceModel('tag/customer_collection');
    }

    public function getTaggedProductsUrl()
    {
        return Mage::getUrl('tag/product/list', array('tagId' => $this->getTagId()));
    }

    public function getViewTagUrl()
    {
        return Mage::getUrl('tag/customer/view', array('tagId' => $this->getTagId()));
    }

    public function getEditTagUrl()
    {
        return Mage::getUrl('tag/customer/edit', array('tagId' => $this->getTagId()));
    }

    public function getRemoveTagUrl()
    {
        return Mage::getUrl('tag/customer/remove', array('tagId' => $this->getTagId()));
    }
    
    public function getPopularCollection()
    {
        return Mage::getResourceModel('tag/popular_collection');
    }
}