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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog product media config
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Product_Media_Config implements Mage_Media_Model_Image_Config_Interface
{

        public function getBaseMediaPath()
        {
            return Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product';
        }

        public function getBaseMediaUrl()
        {
            return Mage::getBaseUrl('media') . 'catalog/product';
        }

        public function getBaseTmpMediaPath()
        {
            return Mage::getBaseDir('media') . DS . 'tmp' . DS . 'catalog' . DS . 'product';
        }

        public function getBaseTmpMediaUrl()
        {
            return Mage::getBaseUrl('media') . 'tmp/catalog/product';
        }

        public function getMediaUrl($file)
        {
            if(in_array(substr($file, 0, 1), array('/'))) {
                return $this->getBaseMediaUrl() . $file;
            }

            return $this->getBaseMediaUrl() . '/' . $file;
        }

        public function getMediaPath($file)
        {
            if(in_array(substr($file, 0, 1), array('/', DIRECTORY_SEPARATOR))) {
                return $this->getBaseMediaPath() . DIRECTORY_SEPARATOR . substr($file, 1);
            }

            return $this->getBaseMediaPath() . DIRECTORY_SEPARATOR . $file;
        }

        public function getTmpMediaUrl($file)
        {
            if(in_array(substr($file, 0, 1), array('/'))) {
                return $this->getBaseTmpMediaUrl() . $file;
            }

            return $this->getBaseTmpMediaUrl() . '/' . $file;
        }

        public function getTmpMediaPath($file)
        {
            if(in_array(substr($file, 0, 1), array('/', DIRECTORY_SEPARATOR))) {
                return $this->getBaseTmpMediaPath() . DIRECTORY_SEPARATOR . substr($file, 1);
            }

            return $this->getBaseTmpMediaPath() . DIRECTORY_SEPARATOR . $file;
        }
}