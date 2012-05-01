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

class Mage_Core_Model_Email_Template_Filter extends Varien_Filter_Template
{
    protected $_useAbsoluteLinks = false;
    protected static $_urlInstance;

    public function setUseAbsoluteLinks($flag)
    {
        $this->_useAbsoluteLinks = $flag;
        return $this;
    }

	public function blockDirective($construction)
	{
		$blockParameters = $this->_getIncludeParameters($construction[2]);
		$layout = Mage::app()->getFrontController()->getAction()->getLayout();

		if (isset($blockParameters['type'])) {
    		$type = $blockParameters['type'];
    		$block = $layout->createBlock($type, null, $blockParameters);
		} elseif (isset($blockParameters['id'])) {
		    $block = $layout->createBlock('cms/block');
		    if ($block) {
    		    $block->setBlockId($blockParameters['id'])
    		        ->setBlockParams($blockParameters);
		    }
		}
		if (!$block) {
		    return '';
		}
		if (isset($blockParameters['output'])) {
		    $method = $blockParameters['output'];
		}
		if (!isset($method) || !is_string($method) || !is_callable(array($block, $method))) {
		    $method = 'toHtml';
		}
        return $block->$method();
	}

	protected function _getBlockParameters($value)
	{
        $tokenizer = new Varien_Filter_Template_Tokenizer_Parameter();
        $tokenizer->setString($value);

        return $tokenizer->tokenize();
	}

	public function skinDirective($construction)
	{
		$params = $this->_getIncludeParameters($construction[2]);
		$params['_absolute'] = $this->_useAbsoluteLinks;

		$url = Mage::getDesign()->getSkinUrl($params['url'], $params);

		return $url;
	}

	public function storeDirective($construction)
	{
    	$params = $this->_getIncludeParameters($construction[2]);
    	$params['_absolute'] = $this->_useAbsoluteLinks;

    	$path = $params['url'];
    	unset($params['url']);

        if (!self::$_urlInstance) {
            self::$_urlInstance = Mage::getModel('core/url');
        }

    	$url = self::$_urlInstance->getUrl($path, $params);

    	return $url;
	}

}