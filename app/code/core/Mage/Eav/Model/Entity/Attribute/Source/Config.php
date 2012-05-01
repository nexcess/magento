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


/**
 * Entity/Attribute/Model - attribute selection source from configuration
 *
 * @category   Mage
 * @package    Mage_Eav
 */
class Mage_Eav_Model_Entity_Attribute_Source_Config extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Retrieve all options for the source from configuration
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array();
            return $this->_options;
            if (!$this->_options) {
                $rootNode = false;
                if ($this->getConfig()->rootNode) {
                    $rootNode = Mage::getConfig()->getNode((string)$this->getConfig()->rootNode);
                } elseif ($this->getConfig()->rootNodeXpath) {
                    $rootNode = Mage::getConfig()->getXpath((string)$this->getConfig()->rootNode);
                }

                if (!$rootNode) {
                    $rootNode = $this->getConfig()->options;
                }

                if (!$rootNode) {
                    throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('No options root node found'));
                }
                foreach ($rootNode->children() as $option) {
                    //$this->_options[(string)$option->value] = (string)$option->label;
                    $this->_options[] = array(
                        'value' => (string)$option->value,
                        'label' => (string)$option->label
                    );
                }
            }
        }

        return $this->_options;
    }

}
