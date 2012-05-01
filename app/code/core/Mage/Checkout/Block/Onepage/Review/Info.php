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
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * One page checkout status
 *
 * @category   Mage
 * @package    Mage_Checkout
 */
class Mage_Checkout_Block_Onepage_Review_Info extends Mage_Checkout_Block_Onepage_Abstract
{    
    public function getItems()
    {
		/*$priceFilter = Mage::app()->getStore()->getPriceFilter();
        $itemsFilter = new Varien_Filter_Object_Grid();
        $itemsFilter->addFilter(new Varien_Filter_Sprintf('%d'), 'qty');
        $itemsFilter->addFilter($priceFilter, 'price');
        $itemsFilter->addFilter($priceFilter, 'row_total');
        return $itemsFilter->filter($this->getQuote()->getAllItems());*/
		return $this->getQuote()->getAllItems();
    }
    
    public function getTotals()
    {
        /*$totalsFilter = new Varien_Filter_Object_Grid();
        $totalsFilter->addFilter(Mage::app()->getStore()->getPriceFilter(), 'value');
        return $totalsFilter->filter($this->getQuote()->getTotals());*/
        return $this->getQuote()->getTotals();
    }
}