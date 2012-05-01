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
 * @package    Mage_Customer
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Address format renderer default
 *
 * @category   Mage
 * @package    Mage_Customer
 */
class Mage_Customer_Block_Address_Renderer_Default extends Mage_Core_Block_Abstract implements Mage_Customer_Block_Address_Renderer_Interface
{
    /**
     * Format type object
     *
     * @var Varien_Object
     */
    protected $_type;

    /**
     * Retrive format type object
     *
     * @return Varien_Object
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Retrive format type object
     *
     * @param  Varien_Object $type
     * @return Mage_Customer_Model_Address_Renderer_Default
     */
    public function setType(Varien_Object $type)
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * Render address
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return string
     */
    public function render(Mage_Customer_Model_Address_Abstract $address)
    {
        $format        = $this->getType()->getDefaultFormat();
        $countryFormat = $address->getCountryModel()->getFormat($this->getType()->getCode());

        $address->getRegion();
        $address->getCountry();
        $address->explodeStreetAddress();

        if ($countryFormat) {
            $format = $countryFormat->getFormat();
        }

        $formater = new Varien_Filter_Template();
        $data = $address->getData();
        if ($this->getType()->getHtmlEscape()) {
            foreach ($data as $key => $value) {
            	$data[$key] = $this->htmlEscape($value);
            }
        }
        $formater->setVariables(array_merge($data, array('country'=>$address->getCountryModel()->getName())));
        return $formater->filter($format);
    }

}
