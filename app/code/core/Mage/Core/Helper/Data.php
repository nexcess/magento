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
 * Core data helper
 *
 */
class Mage_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_crypt;

    /**
     * Convert and format price value for current application store
     *
     * @param   float $value
     * @param   bool $format
     * @return  mixed
     */
    public static function currency($value, $format=true)
    {
        try {
            $value = Mage::app()->getStore()->convertPrice($value, $format);
        }
        catch (Exception $e){
            $value = $e->getMessage();
        }
    	return $value;
    }

    /**
     * Format and convert currency using current store option
     *
     * @param   float $value
     * @return  string
     */
    public function formatCurrency($value)
    {
        return $this->currency($value, true);
    }

    public function formatPrice($price)
    {
        return Mage::app()->getStore()->formatPrice($price);
    }

    /**
     * Format date using current locale options
     *
     * @param   date $date
     * @param   string $format
     * @param   bool $showTime
     * @return  string
     */
    public function formatDate($date=null, $format='short', $showTime=false)
    {
        if (Mage_Core_Model_Locale::FORMAT_TYPE_FULL    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_LONG    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM  !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT   !==$format) {
            return $date;
        }
        if ($date && !strtotime($date)) {
            return '';
        }
        if (is_null($date)) {
            $date = Mage::app()->getLocale()->date(time(), null, null, $showTime);
        }
        else {
            $date = Mage::app()->getLocale()->date(strtotime($date), null, null, $showTime);
        }

        if ($showTime) {
            $format = Mage::app()->getLocale()->getDateTimeFormat($format);
        }
        else {
            $format = Mage::app()->getLocale()->getDateFormat($format);
        }

        return $date->toString($format);
    }

    /**
     * Format time using current locale options
     *
     * @param   date $date
     * @param   string $format
     * @param   bool $showTime
     * @return  string
     */
    public function formatTime($time=null, $format='short', $showDate=false)
    {
        if (Mage_Core_Model_Locale::FORMAT_TYPE_FULL    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_LONG    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM  !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT   !==$format) {
            return $date;
        }

        if (is_null($time)) {
            $date = Mage::app()->getLocale()->date(time());
        }
        else {
            $date = Mage::app()->getLocale()->date(strtotime($time));
        }

        if ($showDate) {
            $format = Mage::app()->getLocale()->getDateTimeFormat($format);
        }
        else {
            $format = Mage::app()->getLocale()->getTimeFormat($format);
        }

        return $date->toString($format);
    }

    /**
     * Encrypt data using application key
     *
     * @param   string $data
     * @return  string
     */
    public function encrypt($data)
    {
        if (!Mage::app()->isInstalled()) {
            return $data;
        }
        $result = base64_encode($this->_getCrypt()->encrypt((string)$data));
        return $result;
    }

    /**
     * Decrypt data using application key
     *
     * @param   string $data
     * @return  string
     */
    public function decrypt($data)
    {
        if (!Mage::app()->isInstalled()) {
            return $data;
        }
        $result = trim($this->_getCrypt()->decrypt(base64_decode((string)$data)));
        return $result;
    }

    public function validateKey($key)
    {
        return $this->_getCrypt($key);
    }

    protected function _getCrypt($key=null)
    {
        if (!$this->_crypt) {
            if (is_null($key)) {
                $key = (string)Mage::getConfig()->getNode('global/crypt/key');
            }
            $this->_crypt = Varien_Crypt::factory()->init($key);
        }
        return $this->_crypt;
    }

    public function getRandomString($len, $chars=null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
		mt_srand(10000000*(double)microtime());
		for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
			$str .= $chars[mt_rand(0, $lc)];
		}
		return $str;
    }

    /**
     * Generate salted hash from password
     *
     * @param string $password
     * @param string|integer|boolean $salt
     */
    public function getHash($password, $salt=false)
    {
        if (is_integer($salt)) {
            $salt = $this->getRandomString($salt);
        }
        return $salt===false ? md5($password) : md5($salt.$password).':'.$salt;
    }

    public function validateHash($password, $hash)
    {
        $hashArr = explode(':', $hash);
        switch (count($hashArr)) {
            case 1:
                return md5($password) === $hash;
            case 2:
                return md5($hashArr[1].$password) === $hashArr[0];
            default:
                Mage::throwException('Invalid hash: '.$hash);
        }
    }

    /**
     * Retrieve store identifier
     *
     * @param   mixed $store
     * @return  int
     */
    public function getStoreId($store=null)
    {
        return Mage::app()->getStore($store)->getId();
    }

    public function removeAccents($string, $german=false)
    {
        static $replacements;

        if (empty($replacements[$german])) {
            $subst = array(
                // single ISO-8859-1 letters
                192=>'A', 193=>'A', 194=>'A', 195=>'A', 196=>'A', 197=>'A', 199=>'C', 208=>'D', 200=>'E', 201=>'E', 202=>'E', 203=>'E', 204=>'I', 205=>'I', 206=>'I', 207=>'I', 209=>'N', 210=>'O', 211=>'O', 212=>'O', 213=>'O', 214=>'O', 216=>'O', 138=>'S', 217=>'U', 218=>'U', 219=>'U', 220=>'U', 221=>'Y', 142=>'Z', 224=>'a', 225=>'a', 226=>'a', 227=>'a', 228=>'a', 229=>'a', 231=>'c', 232=>'e', 233=>'e', 234=>'e', 235=>'e', 236=>'i', 237=>'i', 238=>'i', 239=>'i', 241=>'n', 240=>'o', 242=>'o', 243=>'o', 244=>'o', 245=>'o', 246=>'o', 248=>'o', 154=>'s', 249=>'u', 250=>'u', 251=>'u', 252=>'u', 253=>'y', 255=>'y', 158=>'z',
                // HTML entities
                258=>'A', 260=>'A', 262=>'C', 268=>'C', 270=>'D', 272=>'D', 280=>'E', 282=>'E', 286=>'G', 304=>'I', 313=>'L', 317=>'L', 321=>'L', 323=>'N', 327=>'N', 336=>'O', 340=>'R', 344=>'R', 346=>'S', 350=>'S', 354=>'T', 356=>'T', 366=>'U', 368=>'U', 377=>'Z', 379=>'Z', 259=>'a', 261=>'a', 263=>'c', 269=>'c', 271=>'d', 273=>'d', 281=>'e', 283=>'e', 287=>'g', 305=>'i', 322=>'l', 314=>'l', 318=>'l', 324=>'n', 328=>'n', 337=>'o', 341=>'r', 345=>'r', 347=>'s', 351=>'s', 357=>'t', 355=>'t', 367=>'u', 369=>'u', 378=>'z', 380=>'z',
                // ligatures
                198=>'Ae', 230=>'ae', 140=>'Oe', 156=>'oe', 223=>'ss',
            );

            if ($german) {
                // umlauts
                $subst = array_merge($subst, array(196=>'Ae', 228=>'ae', 214=>'Oe', 246=>'oe', 220=>'Ue', 252=>'ue'));
            }

            $replacements[$german] = array();
            foreach ($subst as $k=>$v) {
                $replacements[$german][$k<256 ? chr($k) : '&#'.$k.';'] = $v;
            }
        }

        // convert string from default database format (UTF-8)
        // to encoding which replacement arrays made with (ISO-8859-1)
        if ($s = @iconv('UTF-8', 'ISO-8859-1', $string)) {
            $string = $s;
        }

        // Replace
        $string = strtr($string, $replacements[$german]);

        return $string;
    }

    public function isDevAllowed($storeId=null)
    {
        $allow = true;

        $allowedIps = Mage::getStoreConfig('dev/restrict/allow_ips', $storeId);
        if (!empty($allowedIps) && isset($_SERVER['REMOTE_ADDR'])) {
            $allowedIps = preg_split('#\s*,\s*#', $allowedIps, null, PREG_SPLIT_NO_EMPTY);
            if (array_search($_SERVER['REMOTE_ADDR'], $allowedIps)===false
                && array_search($_SERVER['HTTP_HOST'], $allowedIps)===false) {
                $allow = false;
            }
        }

        return $allow;
    }

    public function getCacheTypes()
    {
        $types = array();
        $config = Mage::getConfig()->getNode('global/cache/types');
        foreach ($config->children() as $type=>$node) {
            $types[$type] = (string)$node->label;
        }
        return $types;
    }

}
