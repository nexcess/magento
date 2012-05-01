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
 * Poll block
 *
 * @file        Poll.php
 */

class Mage_Poll_Block_ActivePoll extends Mage_Core_Block_Template
{
    protected $_templates, $_voted;

    public function __construct()
    {
        parent::__construct();

        $pollModel = Mage::getModel('poll/poll');
        $votedIds = $pollModel->getVotedPollsIds();
        $pollId = ( Mage::getSingleton('core/session')->getJustVotedPoll() )
            ? Mage::getSingleton('core/session')->getJustVotedPoll()
            : $pollModel->setExcludeFilter($votedIds)->setStoreFilter(Mage::app()->getStore()->getId())->getRandomId();
        $poll = $pollModel->load($pollId);

        if( !$pollId || in_array($pollId, $votedIds) ) {
            return false;
        }

        $pollAnswers = Mage::getModel('poll/poll_answer')
            ->getResourceCollection()
            ->addPollFilter($pollId)
            ->load()
            ->countPercent($poll);

        $this->assign('poll', $poll)
             ->assign('poll_answers', $pollAnswers)
             ->assign('action', Mage::getUrl('poll/vote/add', array('poll_id' => $pollId)));

        $this->_voted = Mage::getModel('poll/poll')->isVoted($pollId);
        Mage::getSingleton('core/session')->setJustVotedPoll(false);
    }

    public function setPollTemplate($template, $type)
    {
        $this->_templates[$type] = $template;
        return $this;
    }

    protected function _toHtml()
    {
        if( $this->_voted === true ) {
            $this->setTemplate($this->_templates['results']);
        } else {
            $this->setTemplate($this->_templates['poll']);
        }
        return parent::_toHtml();
    }
}
