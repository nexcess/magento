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
 * @package    Mage_GoogleCheckout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_GoogleCheckout_Model_Api_Xml_Checkout extends Mage_GoogleCheckout_Model_Api_Xml_Abstract
{
    protected $_currency;
    protected $_shippingCalculated = false;

    protected function _getApiUrl()
    {
        $url = $this->_getBaseApiUrl();
        $url .= 'merchantCheckout/Merchant/'.$this->getMerchantId();
        return $url;
    }

    public function checkout()
    {
        $quote = $this->getQuote();
        if (!($quote instanceof Mage_Sales_Model_Quote)) {
            Mage::throwException('Invalid quote');
        }

        $xml = <<<EOT
<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">
    <shopping-cart>
{$this->_getItemsXml()}
{$this->_getMerchantPrivateDataXml()}
{$this->_getCartExpirationXml()}
    </shopping-cart>
    <checkout-flow-support>
{$this->_getMerchantCheckoutFlowSupportXml()}
    </checkout-flow-support>
    <order-processing-support>
{$this->_getRequestInitialAuthDetailsXml()}
    </order-processing-support>
</checkout-shopping-cart>
EOT;
#echo "<xmp>".$xml."</xmp>";
        $result = $this->_call($xml);

        $this->setRedirectUrl($result->{'redirect-url'});

        return $this;
    }

    protected function _getItemsXml()
    {
        $xml = <<<EOT
        <items>

EOT;
        $weightUnit = 'LB';
        foreach ($this->getQuote()->getAllItems() as $item) {
            $digital = $item->getIsVirtual() ? 'true' : 'false';
            $xml .= <<<EOT
            <item>
                <merchant-item-id><![CDATA[{$item->getSku()}]]></merchant-item-id>
                <item-name><![CDATA[{$item->getName()}]]></item-name>
                <item-description><![CDATA[{$item->getDescription()}]]></item-description>
                <unit-price currency="{$this->getCurrency()}">{$item->getBaseCalculationPrice()}</unit-price>
                <quantity>{$item->getQty()}</quantity>
                <item-weight unit="{$weightUnit}" value="{$item->getWeight()}" />
                <tax-table-selector>{$item->getTaxClassId()}</tax-table-selector>
                {$this->_getDigitalContentXml($item)}
                {$this->_getMerchantPrivateItemDataXml($item)}
            </item>

EOT;
        }

        if ($discount = (float)$this->getQuote()->getShippingAddress()->getBaseDiscountAmount()) {
            $discount = -$discount;
            $xml .= <<<EOT
            <item>
                <merchant-item-id>_INTERNAL_DISCOUNT_</merchant-item-id>
                <item-name>{$this->__('Cart Discount')}</item-name>
                <item-description>{$this->__('Virtual item to reflect discount total')}</item-description>
                <unit-price currency="{$this->getCurrency()}">{$discount}</unit-price>
                <quantity>1</quantity>
                <item-weight unit="{$weightUnit}" value="0.01" />
                <tax-table-selector>none</tax-table-selector>
            </item>

EOT;
        }
        $xml .= <<<EOT
        </items>
EOT;
        return $xml;
    }

    protected function _getDigitalContentXml($item)
    {
        $xml = <<<EOT
EOT;
        return $xml;
    }

    protected function _getMerchantPrivateItemDataXml($item)
    {
        $xml = <<<EOT
            <merchant-private-item-data>
                <quote-item-id>{$item->getEntityId()}</quote-item-id>
            </merchant-private-item-data>
EOT;
        return $xml;
    }
    protected function _getMerchantPrivateDataXml()
    {
        $xml = <<<EOT
            <merchant-private-data>
                <quote-id><![CDATA[{$this->getQuote()->getId()}]]></quote-id>
            </merchant-private-data>
EOT;
        return $xml;
    }

    protected function _getCartExpirationXml()
    {
        $xml = <<<EOT
EOT;
        return $xml;
    }

    protected function _getMerchantCheckoutFlowSupportXml()
    {
        $xml = <<<EOT
        <merchant-checkout-flow-support>
            <edit-cart-url><![CDATA[{$this->_getEditCartUrl()}]]></edit-cart-url>
            <continue-shopping-url><![CDATA[{$this->_getContinueShoppingUrl()}]]></continue-shopping-url>
            {$this->_getRequestBuyerPhoneNumberXml()}
            {$this->_getMerchantCalculationsXml()}
            {$this->_getShippingMethodsXml()}
            {$this->_getTaxTablesXml()}
            {$this->_getParameterizedUrlsXml()}
            {$this->_getPlatformIdXml()}
            {$this->_getAnalyticsDataXml()}
        </merchant-checkout-flow-support>
EOT;
        return $xml;
    }

    protected function _getRequestBuyerPhoneNumberXml()
    {
        $requestPhone = Mage::getStoreConfig('google/checkout/request_phone') ? 'true' : 'false';
        $xml = <<<EOT
            <request-buyer-phone-number>{$requestPhone}</request-buyer-phone-number>
EOT;
        return $xml;
    }

    protected function _getMerchantCalculationsXml()
    {
        $xml = <<<EOT
            <merchant-calculations>
                <merchant-calculations-url><![CDATA[{$this->_getCalculationsUrl()}]]></merchant-calculations-url>
            </merchant-calculations>
EOT;
        return $xml;
    }

    protected function _getShippingMethodsXml()
    {
        $xml = <<<EOT
            <shipping-methods>
                {$this->_getCarrierCalculatedShippingXml()}
                {$this->_getFlatRateShippingXml()}
                {$this->_getMerchantCalculatedShippingXml()}
                {$this->_getPickupXml()}
            </shipping-methods>
EOT;
        return $xml;
    }

    protected function _getCarrierCalculatedShippingXml()
    {
        /*
        we want to send ONLY ONE shipping option to google
        */
        if ($this->_shippingCalculated) {
            return '';
        }

        $active = Mage::getStoreConfigFlag('google/checkout_shipping_carrier/active');
        $methods = Mage::getStoreConfig('google/checkout_shipping_carrier/methods');
        if (!$active || !$methods) {
            return '';
        }

        $country = Mage::getStoreConfig('shipping/origin/country_id');
        $region = Mage::getStoreConfig('shipping/origin/region_id');
        $postcode = Mage::getStoreConfig('shipping/origin/postcode');
        $city = Mage::getStoreConfig('shipping/origin/city');

        $sizeUnit = 'IN';#Mage::getStoreConfig('google/checkout_shipping_carrier/default_unit');
        $defPrice = (float)Mage::getStoreConfig('google/checkout_shipping_carrier/default_price');
        $width = Mage::getStoreConfig('google/checkout_shipping_carrier/default_width');
        $height = Mage::getStoreConfig('google/checkout_shipping_carrier/default_height');
        $length = Mage::getStoreConfig('google/checkout_shipping_carrier/default_length');

        $addressCategory = Mage::getStoreConfig('google/checkout_shipping_carrier/address_category');

//      $taxRate = $this->_getShippingTaxRate();
//      <additional-variable-charge-percent>{$taxRate}</additional-variable-charge-percent>

        $xml = <<<EOT
                <carrier-calculated-shipping>
                    <shipping-packages>
                        <shipping-package>
                            <ship-from id="Origin">
                                <city>{$city}</city>
                                <region>{$region}</region>
                                <postal-code>{$postcode}</postal-code>
                                <country-code>{$country}</country-code>
                            </ship-from>
                            <width unit="{$sizeUnit}" value="{$width}"/>
                            <height unit="{$sizeUnit}" value="{$height}"/>
                            <length unit="{$sizeUnit}" value="{$length}"/>
                            <delivery-address-category>{$addressCategory}</delivery-address-category>
                        </shipping-package>
                    </shipping-packages>
                    <carrier-calculated-shipping-options>
EOT;

        foreach (explode(',', $methods) as $method) {
            list($company, $type) = explode('/', $method);
            $xml .= <<<EOT
                        <carrier-calculated-shipping-option>
                            <shipping-company>{$company}</shipping-company>
                            <shipping-type>{$type}</shipping-type>
                            <price currency="{$this->getCurrency()}">{$defPrice}</price>
                        </carrier-calculated-shipping-option>
EOT;
        }

        $xml .= <<<EOT
                    </carrier-calculated-shipping-options>
                </carrier-calculated-shipping>
EOT;
        $this->_shippingCalculated = true;
        return $xml;
    }

    protected function _getFlatRateShippingXml()
    {
        /*
        we want to send ONLY ONE shipping option to google
        */
        if ($this->_shippingCalculated) {
            return '';
        }

        if (!Mage::getStoreConfigFlag('google/checkout_shipping_flatrate/active')) {
            return '';
        }

        for ($xml='', $i=1; $i<=3; $i++) {
            $title = Mage::getStoreConfig('google/checkout_shipping_flatrate/title_'.$i);
            $price = Mage::getStoreConfig('google/checkout_shipping_flatrate/price_'.$i);

            if (empty($title) || empty($price) && '0'!==$price) {
                continue;
            }

            $xml .= <<<EOT
                <flat-rate-shipping name="{$title}">
                    <price currency="{$this->getCurrency()}">{$price}</price>
                </flat-rate-shipping>
EOT;
        }
        $this->_shippingCalculated = true;
        return $xml;
    }

    protected function _getMerchantCalculatedShippingXml()
    {
        /*
        we want to send ONLY ONE shipping option to google
        */
        if ($this->_shippingCalculated) {
            return '';
        }

        $active = Mage::getStoreConfigFlag('google/checkout_shipping_merchant/active');
        $methods = Mage::getStoreConfig('google/checkout_shipping_merchant/allowed_methods');

        if (!$active || !$methods) {
            return '';
        }

        $methods = unserialize($methods);

        $xml = '';
        foreach ($methods['method'] as $i=>$method) {
            if (!$i || !$method) {
                continue;
            }
            list($carrierCode, $methodCode) = explode('/', $method);
            if ($carrierCode) {
                $carrier = Mage::getModel('shipping/shipping')->getCarrierByCode($carrierCode);
                $allowedMethods = $carrier->getAllowedMethods();

                if (isset($allowedMethods[$methodCode])) {
                    $method = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
                    $method .= ' - '.$allowedMethods[$methodCode];
                }

                $defaultPrice = $methods['price'][$i];

                $xml .= <<<EOT
                    <merchant-calculated-shipping name="{$method}">
                        <price currency="{$this->getCurrency()}">{$defaultPrice}</price>
                    </merchant-calculated-shipping>
EOT;
            }
        }
        $this->_shippingCalculated = true;
        return $xml;
    }

    protected function _getPickupXml()
    {
        if (!Mage::getStoreConfig('google/checkout_shipping_pickup/active')) {
            return '';
        }

        $title = Mage::getStoreConfig('google/checkout_shipping_pickup/title');
        $price = Mage::getStoreConfig('google/checkout_shipping_pickup/price');

        $xml = <<<EOT
                <pickup name="{$title}">
                    <price currency="{$this->getCurrency()}">{$price}</price>
                </pickup>
EOT;
        return $xml;
    }

    protected function _getShippingTaxRate()
    {
        $shippingTaxRate = 0;
        if ($shippingTaxClass = Mage::getStoreConfig('sales/tax/shipping_tax_class')) {
            if (Mage::getStoreConfig('sales/tax/based_on')==='origin') {
                $shippingTaxRate = Mage::helper('tax')->getCatalogTaxRate($shippingTaxClass);
                $shippingTaxed = 'true';
            }
        }
        return $shippingTaxRate;
    }

    protected function _getTaxTablesXml()
    {
        $shippingTaxRate = $this->_getShippingTaxRate()/100;
        $shippingTaxed = $shippingTaxRate>0 ? 'true' : 'false';

        $xml = <<<EOT
            <tax-tables merchant-calculated="true">
                <default-tax-table>
                    <tax-rules>
                        <default-tax-rule>
                            <tax-area>
                                <world-area/>
                            </tax-area>
                            <rate>{$shippingTaxRate}</rate>
                            <shipping-taxed>{$shippingTaxed}</shipping-taxed>
                        </default-tax-rule>
                    </tax-rules>
                </default-tax-table>
                <alternate-tax-tables>
                    <alternate-tax-table name="none" standalone="false">
                        <alternate-tax-rules>
                            <alternate-tax-rule>
                                <tax-area>
                                    <world-area/>
                                </tax-area>
                                <rate>0</rate>
                            </alternate-tax-rule>
                        </alternate-tax-rules>
                    </alternate-tax-table>

EOT;
        foreach ($this->_getTaxRules() as $group=>$taxRates) {
            $xml .= <<<EOT
                    <alternate-tax-table name="{$group}" standalone="false">
                        <alternate-tax-rules>

EOT;
            foreach ($taxRates as $rate) {
                $shipping = !empty($rate['tax_shipping']) ? 'true' : 'false';

                $xml .= <<<EOT
                            <alternate-tax-rule>
                                <tax-area>

EOT;
                if ($rate['country']==='US') {
                    if (!empty($rate['postcode']) && $rate['postcode']!=='*') {
                        $xml .= <<<EOT
                                    <us-zip-area>
                                        <zip-pattern>{$rate['postcode']}</zip-pattern>
                                    </us-zip-area>

EOT;
                    } elseif (!empty($rate['state'])) {
                        $xml .= <<<EOT
                                    <us-state-area>
                                        <state>{$rate['state']}</state>
                                    </us-state-area>

EOT;
                    } else {
                        $xml .= <<<EOT
                                    <us-zip-area>
                                        <zip-pattern>*</zip-pattern>
                                    </us-zip-area>

EOT;
                    }
                } else {
                    if (!empty($rate['postcode'])) {
                        $xml .= <<<EOT
                                    <postal-area>
                                        <country-code>{$rate['country']}</country-code>
EOT;
                        if (!empty($rate['postcode']) && $rate['postcode']!=='*') {
                            $xml .= <<<EOT
                                        <postal-code-pattern>{$rate['postcode']}</postal-code-pattern>

EOT;
                        }
                        $xml .= <<<EOT
                                    </postal-area>

EOT;
                    }
                }
                $xml .= <<<EOT
                                </tax-area>
                                <rate>{$rate['value']}</rate>
                            </alternate-tax-rule>

EOT;
            }
            $xml .= <<<EOT
                        </alternate-tax-rules>
                    </alternate-tax-table>

EOT;
        }

        $xml .= <<<EOT
                </alternate-tax-tables>
            </tax-tables>

EOT;
        return $xml;
    }

    protected function _getTaxRules()
    {
        $customerGroup = $this->getQuote()->getCustomerGroupId();
        if (!$customerGroup) {
            $customerGroup = Mage::getStoreConfig('customer/create_account/default_group', $this->getQuote()->getStoreId());
        }
        $customerTaxClass = Mage::getModel('customer/group')->load($customerGroup)->getTaxClassId();

        $rulesArr = Mage::getResourceModel('googlecheckout/tax')
            ->fetchRuleRatesForCustomerTaxClass($customerTaxClass);

        $rules = array();
        foreach ($rulesArr as $rule) {
            $rules[$rule['tax_product_class_id']][] = $rule;
        }

        return $rules;
    }

    protected function _getRequestInitialAuthDetailsXml()
    {
        $xml = <<<EOT
        <request-initial-auth-details>true</request-initial-auth-details>
EOT;
        return $xml;
    }

    protected function _getParameterizedUrlsXml()
    {
        return '';
        $xml = <<<EOT
            <parameterized-urls>
                <parameterized-url url="{$this->_getParameterizedUrl()}" />
            </parameterized-urls>
EOT;
        return $xml;
    }

    protected function _getPlatformIdXml()
    {
        $xml = <<<EOT
            <platform-id>473325629220583</platform-id>
EOT;
        return $xml;
    }

    protected function _getAnalyticsDataXml()
    {
        if (!($analytics = $this->getApi()->getAnalyticsData())) {
            return '';
        }
        $xml = <<<EOT
            <analytics-data><![CDATA[{$analytics}]]></analytics-data>
EOT;
        return $xml;
    }

    protected function _getEditCartUrl()
    {
        return Mage::getUrl('googlecheckout/redirect/cart');
    }

    protected function _getContinueShoppingUrl()
    {
        return Mage::getUrl('googlecheckout/redirect/continue');
    }

    protected function _getNotificationsUrl()
    {
        return $this->_getCallbackUrl();
    }

    protected function _getCalculationsUrl()
    {
        return $this->_getCallbackUrl();
    }

    protected function _getParameterizedUrl()
    {
        return Mage::getUrl('googlecheckout/api/beacon');
    }
}