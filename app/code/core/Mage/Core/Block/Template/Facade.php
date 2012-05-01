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

/**
 * Block, that can insert other blocks as sorted children, depending on conditions.
 * Also it can spawn neighbours and insert blocks there, also as sorted children, depending on conditions.
 * Conditions are implemented in methods, that do actually insert blocks.
 * Data for conditions must be set before calling inserting method.
 *
 * @category   Mage
 * @package    Mage_Core
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Block_Template_Facade extends Mage_Core_Block_Template
{
    private $_neighbours = array();

    /**
     * Just set data, like Varien_Object
     *
     * This method is to be used in layout.
     * In layout it can be understood better, than setSomeKeyBlahBlah()
     *
     * @param string $key
     * @param string $value
     */
    public function setDataByKey($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * Also set data, but take the value from registry by registry key
     *
     * @param string $key
     * @param string $registryKey
     */
    public function setDataByKeyFromRegistry($key, $registryKey)
    {
        $registryItem = Mage::registry($registryKey);
        if (empty($registryItem)) {
            return;
        }
        $value = $registryItem->getData($key);
        $this->setDataByKey($key, $value);
    }

    /**
     * Insert a block, if all data items by specified keys are equal
     *
     * The block must exist in layout.
     * The block will be also inserted, if no data keys or only one key specified.
     *
     * Currently, the block is inserted only *before* all children
     *
     * @param string $blockName
     * @param string $dataKey1
     * @param string $dataKey2
     * @param string $dataKeyN ...
     * @return Mage_Core_Block_Template_Facade
     */
    public function insertBlockIfEquals($blockName)
    {
        $args = func_get_args();
        return $this->_insertBlockIfEquals($this, $args);
    }

    /**
     * Insert a block as a child of neighbour before
     *
     * @see insertBlockIfEquals()
     * @param string $blockName
     * @return Mage_Core_Block_Template
     */
    public function insertBlockBeforeIfEquals($blockName)
    {
        $args = func_get_args();
        return $this->_insertBlockIfEquals($this->_getNeighbour('before'), $args);
    }

    /**
     * Insert a block as a child of neighbour after
     *
     * @see insertBlockIfEquals()
     * @param string $blockName
     * @return Mage_Core_Block_Template
     */
    public function insertBlockAfterIfEquals($blockName)
    {
        $args = func_get_args();
        return $this->_insertBlockIfEquals($this->_getNeighbour('after'), $args);
    }

    /**
     * Get facade neighbour before
     *
     * @return Mage_Core_Block_Template
     */
    public function getBlockBefore()
    {
        return $this->_getNeighbour('before');
    }

    /**
     * Get facade neighbour after
     *
     * @return Mage_Core_Block_Template
     */
    public function getBlockAfter()
    {
        return $this->_getNeighbour('after');
    }

    private function _getNeighbour($name)
    {
        if (!isset($this->_neighbours[$name])) {
            $this->_neighbours[$name] = $this->getLayout()->createBlock('core/template');
        }
        return $this->_neighbours[$name];
    }

    private function _insertBlockIfEquals(Mage_Core_Block_Template $blockInsertTo, array $args)
    {
        $blockName     = array_shift($args);
        $conditionKeys = $args;

        // assume, that conditions keys are passed from layout as array
        if ((count($conditionKeys) > 0) && (is_array($conditionKeys[1]))) {
            $conditionKeys = $conditionKeys[1];
        }

        // evaluate conditions (equality)
        if (!empty($conditionKeys)) {
            foreach ($conditionKeys as $key) {
                if (!isset($this->_data[$key])) {
                    return $blockInsertTo;
                }
            }
            $lastValue = $this->_data[$key];
            foreach ($conditionKeys as $key) {
                if ($this->_data[$key] !== $lastValue)  {
                    return $blockInsertTo;
                }
            }
        }

        return $blockInsertTo->insert($blockName);
    }
}