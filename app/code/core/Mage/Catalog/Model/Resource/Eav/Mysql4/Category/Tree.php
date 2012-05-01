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
 * Category tree model
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree extends Varien_Data_Tree_Dbp
{

    /**
     * Categories resource collection
     *
     * @var Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    protected $_collection;

    /**
     * Enter description here...
     *
     */
    public function __construct()
    {
        $resource = Mage::getSingleton('core/resource');

        parent::__construct(
            $resource->getConnection('catalog_read'),
            $resource->getTableName('catalog/category'),
            array(
                Varien_Data_Tree_Dbp::ID_FIELD       => 'entity_id',
                Varien_Data_Tree_Dbp::PATH_FIELD     => 'path',
                Varien_Data_Tree_Dbp::ORDER_FIELD    => 'position'
            )
        );
    }

    /**
     * Enter description here...
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection $collection
     * @param boolean $sorted
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree
     */
    public function addCollectionData($collection=null, $sorted=false, $exclude=array(), $toLoad=true, $onlyActive = false)
    {
        if (is_null($collection)) {
            $collection = $this->getCollection($sorted);
        } else {
            $this->setCollection($collection);
        }

        if (!is_array($exclude)) {
            $exclude = array($exclude);
        }

        $collection->initCache(
            Mage::app()->getCache(),
            'tree',
            array(Mage_Catalog_Model_Category::CACHE_TAG)
        );

        $nodeIds = array();
        foreach ($this->getNodes() as $node) {
            if (!in_array($node->getId(), $exclude)) {
                $nodeIds[] = $node->getId();
            }
        }
        $collection->addIdFilter($nodeIds);
        if ($onlyActive) {
            $collection->addAttributeToFilter('is_active', 1);
        }

        if($toLoad) {
            $collection->load();

            foreach ($collection as $category) {
                $this->getNodeById($category->getId())->addData($category->getData());
            }
        }

        return $this;
    }

    /**
     * Get categories collection
     *
     * @param boolean $sorted
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function getCollection($sorted=false)
    {
        if (is_null($this->_collection)) {
            $this->_collection = $this->_getDefaultCollection($sorted);
        }
        return $this->_collection;
    }

    /**
     * Enter description here...
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection $collection
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree
     */
    public function setCollection($collection)
    {
        if (!is_null($this->_collection)) {
            destruct($this->_collection);
        }
        $this->_collection = $collection;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param boolean $sorted
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    protected function _getDefaultCollection($sorted=false)
    {
        $collection = Mage::getModel('catalog/category')->getCollection();
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */

        $collection->addAttributeToSelect('name')
            ->addAttributeToSelect('url_key')
            ->addAttributeToSelect('is_active')
            ->joinUrlRewrite();

        if ($sorted) {
            if (is_string($sorted)) {
                // $sorted is supposed to be attribute name
                $collection->addAttributeToSort($sorted);
            } else {
                $collection->addAttributeToSort('name');
            }
        }

        return $collection;
     }

    /**
     * Executing parents move method and cleaning cache after it
     *
     */
    public function move($category, $newParent, $prevNode = null) {
        parent::move($category, $newParent, $prevNode);
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array(Mage_Catalog_Model_Category::CACHE_TAG));
    }

}
