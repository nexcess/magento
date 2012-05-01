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
 * @package    Mage_Backup
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Backup file item model
 *
 * @category   Mage
 * @package    Mage_Backup
 */
class Mage_Backup_Model_Backup extends Varien_Object
{
    /* backup types */
    const BACKUP_DB     = 'db';
    const BACKUP_VIEW   = 'view';
    const BACKUP_MEDIA  = 'media';

    /* internal constants */
    const BACKUP_EXTENSION  = 'backup';
    const COMPRESS_RATE     = 7;

    /**
     * Type of backup file
     *
     * @var string db|media|view
     */
    private $_type  = 'db';

    /**
     * Load backup file info
     *
     * @param string fileName
     * @param string filePath
     * @return Mage_Backup_Model_Backup
     */
    public function load($fileName, $filePath)
    {
        list ($time, $type) = explode("_", substr($fileName, 0, strrpos($fileName, ".")));
        $this->addData(array(
            'id'   => $filePath . DS . $fileName,
            'time' => (int)$time,
            'path' => $filePath,
            'time_formated' => date('Y-m-d H:i:s', (int)$time))
        );
        $this->setType($type);
        return $this;
    }

    /**
     * Checks backup file exists.
     *
     * @return boolean
     */
    public function exists()
    {
        return is_file($this->getPath() . DS . $this->getFileName());
    }

    /**
     * Return file name of backup file
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->getTime() . "_" . $this->getType()
               . "." . self::BACKUP_EXTENSION;
    }

    /**
     * Sets type of file
     *
     * @param string $value db|media|view
     */
    public function setType($value='db')
    {
        if(!in_array($value, array('db','media','view'))) {
            $value = 'db';
        }

        $this->_type = $value;
        $this->setData('type', $this->_type);

        return $this;
    }

    /**
     * Returns type of backup file
     *
     * @return string db|media|view
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set the backup file content
     *
     * @param string $content
     * @return Mage_Backup_Model_Backup
     * @throws Mage_Backup_Exception
     */
    public function setFile(&$content)
    {
        if (!$this->hasData('time') || !$this->hasData('type') || !$this->hasData('path')) {
            Mage::throwException(Mage::helper('backup')->__('Wrong order of creation for new backup'));
        }

        $ioProxy = new Varien_Io_File();
        $ioProxy->setAllowCreateFolders(true);
        $ioProxy->open(array('path'=>$this->getPath()));

        $compress = 0;
        if (extension_loaded("zlib")) {
            $compress = 1;
        }

        $rawContent = '';
        if ( $compress ) {
            $rawContent = gzcompress( $content, self::COMPRESS_RATE );
        } else {
            $rawContent = $content;
        }

        $fileHeaders = pack("ll", $compress, strlen($rawContent));
        $ioProxy->write($this->getFileName(), $fileHeaders . $rawContent);
        return $this;
    }

    /**
     * Return content of backup file
     *
     * @todo rewrite to Varien_IO, but there no possibility read part of files.
     * @return string
     * @throws Mage_Backup_Exception
     */
    public function &getFile()
    {

        if (!$this->exists()) {
            Mage::throwException(Mage::helper('backup')->__("Backup file doesn't exist"));
        }

        $fResource = @fopen($this->getPath() . DS . $this->getFileName(), "rb");
        if (!$fResource) {
            Mage::throwException(Mage::helper('backup')->__("Cannot read backup file"));
        }

        $content = '';
        $compressed = 0;

        $info = unpack("lcompress/llength", fread($fResource, 8));
        if ($info['compress']) { // If file compressed by zlib
            $compressed = 1;
        }

        if ($compressed && !extension_loaded("zlib")) {
            fclose($fResource);
            Mage::throwException(Mage::helper('backup')->__('File compressed with Zlib, but this extension is not installed on server'));
        }

        if ($compressed) {
            $content = gzuncompress(fread($fResource, $info['length']));
        } else {
            $content = fread($fResource, $info['length']);
        }

        fclose($fResource);

        return $content;
    }

    /**
     * Delete backup file
     *
     * @throws Mage_Backup_Exception
     */
    public function deleteFile()
    {
        if (!$this->exists()) {
            Mage::throwException(Mage::helper('backup')->__("Backup file doesn't exist"));
        }

        $ioProxy = new Varien_Io_File();
        $ioProxy->open(array('path'=>$this->getPath()));
        $ioProxy->rm($this->getFileName());
        return $this;
    }

}
