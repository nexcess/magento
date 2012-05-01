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
 * @package    Mage_Poll
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Vote model
 *
 * @category   Mage
 * @package    Mage_Poll
 */
class Mage_Poll_Model_Poll_Vote extends Varien_Object
{
    protected $_pollId;
    protected $_resource;

    public function getId()
    {
        return $this->getPollId();
    }

    public function addVote()
    {
        $this->_getResource()->add($this);
    }

    protected function _getResource()
    {
        if (!$this->_resource) {
        	$this->_resource = Mage::getResourceSingleton('poll/poll_answer_vote');
        }
        return $this->_resource;
    }
}