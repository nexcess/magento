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
 * Catalog product bundle option model
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
 class Mage_Catalog_Model_Product_Bundle_Option extends Mage_Core_Model_Abstract
 {
    protected $_linkCollection = null;

    protected function _construct()
    {
        $this->_init('catalog/product_bundle_option');
    }

    public function getLinkCollection()
    {
        if(is_null($this->_linkCollection)) {
            $this->_linkCollection = Mage::getResourceModel('catalog/product_bundle_option_link_collection')
                ->setOptionId($this->getId())
                ->setStoreId($this->getStoreId());
        }

        return $this->_linkCollection;
    }

    public function toArray(array $arrAttributes = array())
    {
        return $this->_getResource()->toArray($this);
    }

    public function getDataForSave()
    {
        $data = array();
        $data['product_id'] = $this->getProductId();
        return $data;
    }

    public function getPosition() {
        if(strlen($this->getData('position')) > 0 || $this->getData('position')) {
            return $this->getData('position');
        }
        return $this->getDefaultPosition();
    }

    public function getLabel() {
        if(strlen($this->getData('label')) > 0 || $this->getData('label')) {
            return $this->getData('label');
        }
        return $this->getDefaultLabel();
    }
 }
