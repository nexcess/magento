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
 * @package    Mage_Core
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Core_Model_Mysql4_Website extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('core/website', 'website_id');
        $this->_uniqueFields = array(array('field' => 'code', 'title' => Mage::helper('core')->__('Website with the same code')));
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $model)
    {
        if(!preg_match('/^[a-z]+[a-z0-9_]*$/',$model->getCode())) {
            Mage::throwException(Mage::helper('core')->__('Website code should contain only letters (a-z), numbers (0-9) or underscore(_), first character should be a letter'));
        }

        return $this;
    }

    protected function _afterSave(Mage_Core_Model_Abstract $model)
    {
        return $this;
    }

    protected function _afterDelete(Mage_Core_Model_Abstract $model)
    {
        $this->_getWriteAdapter()->delete(
            $this->getTable('core/config_data'),
            $this->_getWriteAdapter()->quoteInto("scope = 'websites' AND scope_id = ?", $model->getWebsiteId())
        );
        return $this;
    }
}