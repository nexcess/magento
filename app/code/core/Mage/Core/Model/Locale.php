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
 * Locale model
 *
 */
class Mage_Core_Model_Locale
{
    /**
     * Default locale name
     */
    const DEFAULT_LOCALE    = 'en_US';
    const DEFAULT_TIMEZONE  = 'UTC';
    const DEFAULT_CURRENCY  = 'USD';

    /**
     * XML path constants
     */
    const XML_PATH_DEFAULT_LOCALE   = 'general/locale/code';
    const XML_PATH_DEFAULT_TIMEZONE = 'general/locale/timezone';
    const XML_PATH_DEFAULT_COUNTRY  = 'general/country/default';
    const XML_PATH_ALLOW_CODES      = 'global/locale/allow/codes';
    const XML_PATH_ALLOW_CURRENCIES = 'global/locale/allow/currencies';
    const XML_PATH_ALLOW_CURRENCIES_INSTALLED = 'system/currency/installed';

    /**
     * Date and time format codes
     */
    const FORMAT_TYPE_FULL  = 'full';
    const FORMAT_TYPE_LONG  = 'long';
    const FORMAT_TYPE_MEDIUM= 'medium';
    const FORMAT_TYPE_SHORT = 'short';

    /**
     * Default locale code
     *
     * @var string
     */
    protected $_defaultLocale;

    /**
     * Locale object
     *
     * @var Zend_Locale
     */
    protected $_locale;

    protected static $_currencyCache = array();

    public function __construct($locale = null)
    {
        Zend_Locale_Data::setCache(Mage::app()->getCache());
        $this->setLocale($locale);
    }

    /**
     * Set default locale code
     *
     * @param   string $locale
     * @return  Mage_Core_Model_Locale
     */
    public function setDefaultLocale($locale)
    {
        $this->_defaultLocale = $locale;
        return $this;
    }

    /**
     * REtrieve default locale code
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        if (!$this->_defaultLocale) {
            $locale = Mage::getStoreConfig(self::XML_PATH_DEFAULT_LOCALE);
            if (!$locale) {
                $locale = self::DEFAULT_LOCALE;
            }
            $this->_defaultLocale = $locale;
        }
        return $this->_defaultLocale;
    }

    /**
     * Set locale
     *
     * @param   strint $locale
     * @return  Mage_Core_Model_Locale
     */
    public function setLocale($locale = null)
    {
        Mage::dispatchEvent('core_locale_set_locale', array('locale'=>$this));
        $this->_locale = new Zend_Locale($this->getDefaultLocale());
        return $this;
    }

    /**
     * Retrieve timezone code
     *
     * @return string
     */
    public function getTimezone()
    {
        return self::DEFAULT_TIMEZONE;
    }

    /**
     * Retrieve currency code
     *
     * @return string
     */
    public function getCurrency()
    {
        return self::DEFAULT_CURRENCY;
    }

    /**
     * Retrieve locale object
     *
     * @return Zend_Locale
     */
    public function getLocale()
    {
        if (!$this->_locale) {
            $this->setLocale();
        }

        return $this->_locale;
    }

    /**
     * Retrieve locale code
     *
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->getLocale()->toString();
    }

    /**
     * Retrieve options array for locale dropdown
     *
     * @return array
     */
    public function getOptionLocales()
    {
        $options    = array();
        $locales    = $this->getLocale()->getLocaleList();
        $languages  = $this->getLocale()->getLanguageTranslationList();
        $countries  = $this->getLocale()->getCountryTranslationList();

        $allowed    = $this->getAllowLocales();
        foreach ($locales as $code=>$active) {
            if (strstr($code, '_')) {
                if (!in_array($code, $allowed)) {
                    continue;
                }
                $data = explode('_', $code);
                if (!isset($languages[$data[0]]) || !isset($countries[$data[1]])) {
                    continue;
                }
                $options[] = array(
                    'value' => $code,
                    'label' => $languages[$data[0]] . ' (' . $countries[$data[1]] . ')'
                );
            }
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve timezone option list
     *
     * @return array
     */
    public function getOptionTimezones()
    {
        $options= array();
        $zones  = $this->getLocale()->getTranslationList('windowstotimezone');
        ksort($zones);
        foreach ($zones as $code=>$name) {
            $name = trim($name);
            $options[] = array(
               'label' => empty($name) ? $code : $name . ' (' . $code . ')',
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve country option list
     *
     * @return array
     */
    public function getOptionCountries()
    {
        $options    = array();
        $countries  = $this->getLocale()->getCountryTranslationList();

        foreach ($countries as $code=>$name) {
            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve currency option list
     *
     * @return unknown
     */
    public function getOptionCurrencies()
    {
        $currencies = $this->getLocale()->getTranslationList('currencytoname');
        $options = array();
        $allowed = $this->getAllowCurrencies();

        foreach ($currencies as $name=>$code) {
            if (!in_array($code, $allowed)) {
                continue;
            }

            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve all currency option list
     *
     * @return unknown
     */
    public function getOptionAllCurrencies()
    {
        $currencies = $this->getLocale()->getTranslationList('currencytoname');
        $options = array();
        foreach ($currencies as $name=>$code) {
            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    protected function _sortOptionArray($option)
    {
        $data = array();
        foreach ($option as $item) {
        	$data[$item['value']] = $item['label'];
        }
        asort($data);
        $option = array();
        foreach ($data as $key => $label) {
        	$option[] = array(
        	   'value' => $key,
        	   'label' => $label
        	);
        }
        return $option;
    }

    /**
     * Retrieve array of allowed locales
     *
     * @return array
     */
    public function getAllowLocales()
    {
        $data = Mage::getConfig()->getNode(self::XML_PATH_ALLOW_CODES)->asArray();
        if ($data) {
            return array_keys($data);
        }
        return array();
    }

    /**
     * Retrieve array of allowed currencies
     *
     * @return unknown
     */
    public function getAllowCurrencies()
    {
        $data = array();
        if (Mage::app()->isInstalled()) {
            $data = Mage::app()->getStore()->getConfig(self::XML_PATH_ALLOW_CURRENCIES_INSTALLED);
            return explode(',', $data);
        }
        else {
            $data = Mage::getConfig()->getNode(self::XML_PATH_ALLOW_CURRENCIES)->asArray();
            if ($data) {
                return array_keys($data);
            }
        }
        return $data;
    }

    /**
     * Retrieve ISO date format
     *
     * @param   string $type
     * @return  string
     */
    public function getDateFormat($type=null)
    {
        return $this->getLocale()->getTranslation($type, 'date');
    }

    /**
     * Retrieve ISO time format
     *
     * @param   string $type
     * @return  string
     */
    public function getTimeFormat($type=null)
    {
        return $this->getLocale()->getTranslation($type, 'time');
    }

    /**
     * Retrieve ISO datetime format
     *
     * @param   string $type
     * @return  string
     */
    public function getDateTimeFormat($type)
    {
        return $this->getDateFormat($type) . ' ' . $this->getTimeFormat($type);
    }

    /**
     * Retrieve date format by strftime function
     *
     * @param   string $type
     * @return  string
     */
    public function getDateStrFormat($type)
    {
        $convert = array('yyyy-MM-ddTHH:mm:ssZZZZ'=>'%c',   'EEEE'=>'%A',   'EEE'=>'%a','D'=>'%j',
                         'MMMM'=>'%B',  'MMM'=>'%b',        'MM'=>'%m',     'M'=>'%m',  'dd'=>'%d',
                         'd'=>'%e',     'yyyy'=>'%Y',       'yy'=>'%y');
        $format = $this->getDateFormat($type);
        foreach ($convert as $key=>$value) {
            $format = preg_replace('/(^|[^%])'.$key.'/', '$1'.$value, $format);
        }
        return $format;
    }

    /**
     * Retrieve time format by strftime function
     *
     * @param   string $type
     * @return  string
     */
    public function getTimeStrFormat($type)
    {
        $convert = array('a'=>'%p', 'hh'=>'%I', 'h'=>'%I', 'HH'=>'%H', 'mm'=>'%M', 'ss'=>'%S', 'z'=>'%Z', 'v'=>'%Z');

        $format = $this->getTimeFormat($type);
        foreach ($convert as $key=>$value) {
            $format = preg_replace('/(^|[^%])'.$key.'/', '$1'.$value, $format);
        }
        return $format;
    }

    /**
     * Create Zend_Date object for current locale
     *
     * @param   mixed $date
     * @param   string $part
     * @return  Zend_Date
     */
    public function date($date=null, $part=null, $locale=null, $useTimezone=true)
    {
        if (is_null($locale)) {
            $locale = $this->getLocale();
        }

        try {
            $date = new Zend_Date($date, $part, $locale);
            if ($useTimezone) {
                if ($timezone = Mage::app()->getStore()->getConfig(self::XML_PATH_DEFAULT_TIMEZONE)) {
                    $date->setTimezone($timezone);
                }
            }
            //$date->add(-(substr($date->get(Zend_Date::GMT_DIFF), 0,3)), Zend_Date::HOUR);
        }
        catch (Exception $e){
            echo $e;
            return null;
        }
        return $date;
    }

    /**
     * Create Zend_Currency object for current locale
     *
     * @param   string $currency
     * @return  Zend_Currency
     */
    public function currency($currency)
    {
        Varien_Profiler::start('locale/currency');
        if (!isset(self::$_currencyCache[$this->getLocaleCode()][$currency])) {
            self::$_currencyCache[$this->getLocaleCode()][$currency] =
                new Zend_Currency($currency, $this->getLocale());
        }
        Varien_Profiler::stop('locale/currency');
        return self::$_currencyCache[$this->getLocaleCode()][$currency];
    }
}
