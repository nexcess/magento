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
 * @package    Mage_Review
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Review helper
 *
 * @category   Mage
 * @package    Mage_Review
 */

class Mage_Review_Block_Helper extends Mage_Core_Block_Template
{
	public function getSummaryHtml($product, $type=null, $displayBlock=0)
	{
	    $this->setDisplayBlock($displayBlock);
        $this->setProduct($product);

	    if( !$product->getRatingSummary() ) {
	        Mage::getModel('review/review')
	           ->getEntitySummary($product, Mage::app()->getStore()->getId());
	    }

	    switch ($type) {
	    	case 'short':
	    		$this->setTemplate('review/helper/summary_short.phtml');
	    		break;

	    	default:
	    		$this->setTemplate('review/helper/summary.phtml');
	    		break;
	    }

		$this->setProduct($product);
		return $this->toHtml();
	}

	public function getAddLink()
	{
	    $params = array(
	       'id'        => $this->getProduct()->getId(),
	       'category'  => $this->getProduct()->getCategoryId()
        );

	    return Mage::getUrl('review/product/list', $params);
	}
}