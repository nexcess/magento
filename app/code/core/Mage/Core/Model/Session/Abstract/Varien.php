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


class Mage_Core_Model_Session_Abstract_Varien extends Varien_Object
{
    public function start($sessionName=null)
    {
    	if (isset($_SESSION)) {
    		return $this;
    	}

        Varien_Profiler::start(__METHOD__.'/setOptions');
        if (is_writable(Mage::getBaseDir('session'))) {
            session_save_path(Mage::getBaseDir('session'));
        }
        Varien_Profiler::stop(__METHOD__.'/setOptions');

        session_module_name('files');
/*
        $sessionResource = Mage::getResourceSingleton('core/session');
        $sessionResource->setSaveHandler();
*/

		if (!is_null($this->getCookieLifetime())) {
			ini_set('session.gc_maxlifetime', $this->getCookieLifetime());
		}
		if (!is_null($this->getCookiePath())) {
			ini_set('session.cookie_path', $this->getCookiePath());
		}
		if (!is_null($this->getCookieDomain()) && strpos($this->getCookieDomain(), '.')!==false) {
			ini_set('session.cookie_domain', $this->getCookieDomain());
		}

		if (!empty($sessionName)) {
		    session_name($sessionName);
		}

        // potential custom logic for session id (ex. switching between hosts)
        $this->setSessionId();

        Varien_Profiler::start(__METHOD__.'/start');

        session_start();
        Varien_Profiler::stop(__METHOD__.'/start');
        return $this;
    }

    public function init($namespace, $sessionName=null)
    {
    	if (!isset($_SESSION)) {
    		$this->start($sessionName);
    	}
        if (!isset($_SESSION[$namespace])) {
        	$_SESSION[$namespace] = array();
        }
        $this->_data = &$_SESSION[$namespace];

        return $this;
    }

    public function getData($key='', $clear=false)
    {
        $data = parent::getData($key);
        if ($clear && isset($this->_data[$key])) {
            unset($this->_data[$key]);
        }
        return $data;
    }

    public function getSessionId()
    {
        return session_id();
    }

    public function setSessionId($id=null)
    {
        if (!is_null($id) && preg_match('#^[0-9a-zA-Z,-]+$#', $id)) {
            session_id($id);
        }
        return $this;
    }

    public function unsetAll()
    {
    	$this->unsetData();
    	return $this;
    }

    public function clear()
    {
        return $this->unsetAll();
    }
}