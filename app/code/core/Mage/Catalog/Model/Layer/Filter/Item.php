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
 * Filter item model
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Layer_Filter_Item extends Varien_Object
{
    public function getFilter()
    {
        $filter = $this->getData('filter');
        if (!is_object($filter)) {
            Mage::throwException(Mage::helper('catalog')->__('Filter must be as object. Set correct filter please'));
        }
        return $filter;
    }
    
    public function getUrl()
    {
        return Mage::getUrl('*/*/*', array('_current'=>true, $this->getFilter()->getRequestVar()=>$this->getValue()));
    }
    
    public function getRemoveUrl()
    {
        return Mage::getUrl('*/*/*', array('_current'=>true, $this->getFilter()->getRequestVar()=>null));
    }
    
    public function getName()
    {
        return $this->getFilter()->getName();
    }
}
