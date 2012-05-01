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
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Eav_Model_Entity_Attribute_Backend_Gallery_Image_Collection extends Varien_Data_Collection_Db
{

    public function __construct($conn=null)
    {
        parent::__construct($conn);
        $this->setItemObjectClass('Mage_Eav_Model_Entity_Attribute_Backend_Gallery_Image');
    }
    
    public function getAttributeBackend()
    {
        return $this->_attributeBackend;
    }
    
    public function setAttributeBackend($attributeBackend)
    {
        $this->_attributeBackend = $attributeBackend;
        return $this;
    }
    
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }
        
        parent::load($printQuery, $logQuery);
        foreach ($this as $_item) {
            $_item->setAttribute($this->getAttributeBackend()->getAttribute());
        }
        return $this;
    }

}
