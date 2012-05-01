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
 * Review list block
 *
 * @category   Mage
 * @package    Mage_Review
 */
class Mage_Review_Block_List extends Mage_Core_Block_Template
{
    protected $_collection;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('review/list.phtml');
        $productId = Mage::app()->getRequest()->getParam('id', false);

        $this->_collection = Mage::getModel('review/review')->getCollection();

        $this->_collection
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addStatusFilter('approved')
            ->addEntityFilter('product', $productId)
            ->setDateOrder();

        $this->assign('reviewCount', $this->count());
        $this->assign('reviewLink', Mage::getUrl('review/product/list', array('id'=>$productId)));
    }

    public function getAddLink()
    {
        $productId = Mage::app()->getRequest()->getParam('id', false);
        return Mage::getUrl('review/product/list', array('id' => $productId));
    }

    public function count()
    {
        return $this->_collection->getSize();
    }

    protected function _toHtml()
    {
        $request    = Mage::app()->getRequest();
        $productId  = $request->getParam('id', false);

        $this->_getCollection()
            ->addRateVotes();

        $this->assign('collection', $this->_collection);

        $backUrl = Mage::getUrl('catalog/product/view/id/'.$productId);
        $this->assign('backLink', $backUrl);

        $pageUrl = clone $request;
        $this->assign('pageUrl', $pageUrl);

        return parent::_toHtml();
    }

    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    protected function _prepareLayout()
    {
        $toolbar = $this->getLayout()->createBlock('page/html_pager', 'review_list.toolbar')
            ->setCollection($this->_getCollection());

        $this->setChild('toolbar', $toolbar);
        return parent::_prepareLayout();
    }

    protected function _getCollection()
    {
        return $this->_collection;
    }

    public function getCollection()
    {
        return $this->_getCollection();
    }

    public function getAverage($ratingVotes)
    {
        $avarage = 0;
        $total  = 0;
        foreach ($ratingVotes as $vote) {
            $avarage+= $vote->getPercent();
            $total ++;
        }

        return $total ? ceil($avarage / $total) : 0;
    }

    protected function _beforeToHtml()
    {
        $this->_getCollection()
            ->setPageSize(10)
            ->load()
            ->addRateVotes();
        return parent::_beforeToHtml();
    }
}
