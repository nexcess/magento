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
 * @package    Mage_Sitemap
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sitemap resource catalog collection model
 *
 * @category   Mage
 * @package    Mage_Sitemap
 */

class Mage_Sitemap_Model_Mysql4_Catalog_Category extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Init resource model (catalog/category)
     */
    protected function _construct()
    {
        $this->_init('catalog/category', 'entity_id');
    }
    
    /**
     * Get category collection array
     * 
     * @return array
     */
    public function getCollection($storeId)
    {
        $categories = array();
        
        $store = Mage::app()->getStore($storeId);
        /* @var $store Mage_Core_Model_Store */
        
        if (!$store) {
            return false;
        }
        
        $select = $this->_getWriteAdapter()->select()
            ->from($this->getMainTable())
            ->where($this->getIdFieldName() . '=?', $store->getRootCategoryId());
        $categoryRow = $this->_getWriteAdapter()->fetchRow($select);
        
        if (!$categoryRow) {
            return false;
        }
        
        $urConditions = array(
            'e.entity_id=ur.category_id',
            $this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
            'ur.product_id IS NULL',
            $this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
        );
        $select = $this->_getWriteAdapter()->select()
            ->from(array('e' => $this->getMainTable()), array($this->getIdFieldName()))
            ->joinLeft(
                array('ur' => $this->getTable('core/url_rewrite')),
                join(' AND ', $urConditions),
                array('url'=>'request_path')
            )
            ->where('e.path LIKE ?', $categoryRow['path'] . '/%')
            ->where('e.is_active=?', 1);
        $query = $this->_getWriteAdapter()->query($select);
        while ($row = $query->fetch()) {
            $category = $this->_prepareCategory($row);
            $categories[$category->getId()] = $category;
        }
        
        return $categories;
    }
    
    /**
     * Prepare category
     * 
     * @param array $categoryRow
     * @return Varien_Object
     */
    protected function _prepareCategory(array $categoryRow)
    {
        $category = new Varien_Object();
    	$category->setId($categoryRow[$this->getIdFieldName()]);
    	$categoryUrl = !empty($categoryRow['url']) ? $categoryRow['url'] : 'catalog/category/view/id/' . $category->getId();
    	$category->setUrl($categoryUrl);
    	return $category;
    }
    
}