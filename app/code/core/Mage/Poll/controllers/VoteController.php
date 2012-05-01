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
 * Poll vote controller
 *
 * @file        Vote.php
 */

class Mage_Poll_VoteController extends Mage_Core_Controller_Front_Action
{
    /**
     * Add vote action
     *
     * @access public
     * @return void
     */
    public function addAction()
    {
        $pollId     = intval( $this->getRequest()->getParam('poll_id') );
        $answerId   = intval( $this->getRequest()->getParam('vote') );

        if( $pollId && $answerId && !Mage::getSingleton('poll/poll')->isVoted($pollId) ) {
            Mage::getSingleton('poll/poll_vote')
                ->setPollId($pollId)
                ->setIpAddress(ip2long($this->getRequest()->getServer('REMOTE_ADDR')))
                ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
                ->setVoteTime(now())
                ->setPollAnswerId($answerId)
                ->addVote();

            Mage::getSingleton('core/session')->setJustVotedPoll($pollId);
            Mage::getSingleton('poll/poll')->setVoted($pollId);
        }

        $this->_redirectReferer();
    }
}