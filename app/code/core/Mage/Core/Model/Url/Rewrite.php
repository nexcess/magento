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
 * Url rewrite model class
 *
 *
 * @category   Mage
 * @package    Mage_Core
 */
class Mage_Core_Model_Url_Rewrite extends Mage_Core_Model_Abstract
{

    const TYPE_CATEGORY = 1;
    const TYPE_PRODUCT  = 2;
    const TYPE_CUSTOM   = 3;
    protected function _construct()
    {
        $this->_init('core/url_rewrite');
    }

    public function loadByRequestPath($path)
    {
        $this->setId(null)->load($path, 'request_path');
        return $this;
    }

    public function loadByIdPath($path)
    {
        $this->setId(null)->load($path, 'id_path');
        return $this;
    }

    public function loadByTags($tags)
    {
        $this->setId(null);

        $loadTags = is_array($tags) ? $tags : explode(',', $tags);

        $search = $this->getResourceCollection();
        foreach ($loadTags as $k=>$t) {
            if (!is_numeric($k)) {
                $t = $k.'='.$t;
            }
            $search->addTagsFilter($t);
        }
        if (!is_null($this->getStoreId())) {
            $search->addStoreFilter($this->getStoreId());
        }

        $search->setPageSize(1)->load();

        if ($search->getSize()>0) {
            foreach ($search as $rewrite) {
                $this->setData($rewrite->getData());
            }
        }

        return $this;
    }

    public function hasOption($key)
    {
        $optArr = explode(',', $this->getOptions());

        return array_search($key, $optArr) !== false;
    }

    public function addTag($tags)
    {
        $curTags = $this->getTags();

        $addTags = is_array($tags) ? $tags : explode(',', $tags);

        foreach ($addTags as $k=>$t) {
            if (!is_numeric($k)) {
                $t = $k.'='.$t;
            }
            if (!in_array($t, $curTags)) {
                $curTags[] = $t;
            }
        }

        $this->setTags($curTags);

        return $this;
    }

    public function removeTag($tags)
    {
        $curTags = $this->getTags();

        $removeTags = is_array($tags) ? $tags : explode(',', $tags);

        foreach ($removeTags as $t) {
            if (!is_numeric($k)) {
                $t = $k.'='.$t;
            }
            if ($key = array_search($t, $curTags)) {
                unset($curTags[$key]);
            }
        }

        $this->setTags(',', $curTags);

        return $this;
    }

    public function rewrite(Zend_Controller_Request_Http $request=null, Zend_Controller_Response_Http $response=null)
    {
        if (!Mage::app()->isInstalled()) {
            return false;
        }
        if (is_null($request)) {
            $request = Mage::app()->getFrontController()->getRequest();
        }
        if (is_null($response)) {
            $response = Mage::app()->getFrontController()->getResponse();
        }
        if (is_null($this->getStoreId()) || false===$this->getStoreId()) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }

        $requestPath = trim($request->getPathInfo(), '/');
        #$requestPath = $request->getPathInfo();
        $this->setId(null)->loadByRequestPath($requestPath);

        if (!$this->getId() && isset($_GET['from_store'])) {
            try {
                $fromStoreId = Mage::app()->getStore($_GET['from_store']);
            }
            catch (Exception $e) {
                return false;
            }

            $this->setId(null)->setStoreId($fromStoreId)->loadByRequestPath($requestPath);
            if (!$this->getId()) {
                return false;
            }
            $this->setId(null)->setStoreId(Mage::app()->getStore()->getId())->loadByIdPath($this->getIdPath());
        }

        if (!$this->getId()) {
            return false;
        }

        $request->setAlias('rewrite_request_path', $this->getRequestPath());
        $targetUrl = $request->getBaseUrl(). '/' . $this->getTargetPath();
        if ($this->hasOption('R')) {
            header("Location: ".$targetUrl);
            exit;
        }

        $request->setRequestUri($targetUrl);
        $request->setPathInfo($this->getTargetPath());

        return true;
    }

}