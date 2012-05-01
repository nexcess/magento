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


class Mage_Poll_Model_Mysql4_Poll_Answer_Vote
{
    protected $_pollVoteTable;
    protected $_pollAnswerTable;
    protected $_pollTable;

    protected $_read;
    protected $_write;

    function __construct()
    {
        $this->_pollVoteTable = Mage::getSingleton('core/resource')->getTableName('poll/poll_vote');
        $this->_pollAnswerTable = Mage::getSingleton('core/resource')->getTableName('poll/poll_answer');
        $this->_pollTable = Mage::getSingleton('core/resource')->getTableName('poll/poll');

        $this->_read = Mage::getSingleton('core/resource')->getConnection('poll_read');
        $this->_write = Mage::getSingleton('core/resource')->getConnection('poll_write');
    }

    function add($vote)
    {
        $this->_write->insert($this->_pollVoteTable, $vote->getData());

        # Increment `poll_answer` votes count
        $pollAnswerData = array(
                            'votes_count' => new Zend_Db_Expr('votes_count+1')
                        );

        $condition = $this->_write->quoteInto("{$this->_pollAnswerTable}.answer_id=?", $vote->getPollAnswerId());
        $this->_write->update($this->_pollAnswerTable, $pollAnswerData, $condition);

        # Increment `poll` votes count
        $pollData = array(
                            'votes_count' => new Zend_Db_Expr('votes_count+1')
                        );

        $condition = $this->_write->quoteInto("{$this->_pollTable}.poll_id=?", $vote->getPollId());
        $this->_write->update($this->_pollTable, $pollData, $condition);
    }
}