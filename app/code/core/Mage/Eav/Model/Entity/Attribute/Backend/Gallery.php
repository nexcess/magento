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
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Image gallery attribute backend
 *
 * @category   Mage
 * @package    Mage_Eav
 */

class Mage_Eav_Model_Entity_Attribute_Backend_Gallery extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{

    /**
     *
     */
    protected $_resourceModel;

	/**
	 * DB connections list
	 *
	 * @var array
	 */
	protected $_connections = array();

	protected $_imageTypes = array();

	protected $_images = null;

	public function __construct()
	{

	}

    /**
     * Set connections for entity operations
     *
     * @param Zend_Db_Adapter_Abstract $read
     * @param Zend_Db_Adapter_Abstract $write
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function setConnection(Zend_Db_Adapter_Abstract $read, Zend_Db_Adapter_Abstract $write=null)
    {
        $this->_connections['read'] = $read;
        $this->_connections['write'] = $write ? $write : $read;
        return $this;
    }

    /**
     * Return DB connection
     *
     * @param	string		$type
     * @return	Zend_Db_Adapter_Abstract
     */
    public function getConnection($type)
    {
    	return $this->_connections[$type];
    }

	public function afterLoad($object)
    {
    	$storeId = $object->getStoreId();

        $attributeId   = $this->getAttribute()->getId();
        $entityId	   = $object->getId();
        $entityIdField = $this->getEntityIdField();

        // TOFIX
        $this->_images = new Mage_Eav_Model_Entity_Attribute_Backend_Gallery_Image_Collection($this->getConnection('read'));

        $this->_images->getSelectSql()
        	->from($this->getTable(), array('value_id', 'value', 'position'))
        	->where('store_id = ?', $storeId)
        	->where($entityIdField . ' = ?', $entityId)
        	->where('attribute_id = ?', $attributeId)
            ->order('position', 'asc');

        $object->setData($this->getAttribute()->getName(), $this->_images->setAttributeBackend($this)->load());
    }


   /**
    *
    * especially developed for copying when we get incoming data as an image collection
    * instead of plain post...
    *
    */
    public function beforeSave($object)
    {
    	$storeId       = $object->getStoreId();
        $attributeId   = $this->getAttribute()->getId();
        $entityId	   = $object->getId();
        $entityIdField = $this->getEntityIdField();
        $entityTypeId  = $this->getAttribute()->getEntity()->getTypeId();

        $connection = $this->getConnection('write');

        $values = $object->getData($this->getAttribute()->getName());

        if(!is_array($values) && is_object($values)) {
            foreach ((array)$values->getItems() as $image) {
                // TOFIX
                $io = new Varien_Io_File();

                $value = $image->getData();

                $data = array();
    		    $data[$entityIdField] 	= $entityId;
    		    $data['attribute_id'] 	= $attributeId;
    		    $data['position']		= $value['position'];
    		    $data['entity_type_id'] = $entityTypeId;
    		    $data['store_id']	  	= $storeId;

    		    if ($entityId) {
		            $connection->insert($this->getTable(), $data);
		            $lastInsertId = $connection->lastInsertId();
    		    }
    		    else {
    		        continue;
    		    }

                unset($newFileName);
                $types = $this->getImageTypes();
                foreach ($types as  $type) {
                    try {
                        $io->open();
                        $path = Mage::getStoreConfig('system/filesystem/upload', 0);
                        $io->cp($path.'/'.$type.'/'.'image_'.$entityId.'_'.$value['value_id'].'.'.'jpg', $path.'/'.$type.'/'.'image_'.$entityId.'_'.$lastInsertId.'.'.'jpg');
                        $io->close();
                    }
                    catch (Exception $e){
                        continue;
                    }
                    $newFileName = 'image_'.$entityId.'_'.$lastInsertId.'.'.'jpg';
    	        }

    	        $condition = array($connection->quoteInto('value_id = ?', $lastInsertId));
                if (isset($newFileName)) {
                    $data = array();
    		        $data['value']		  	= $newFileName;
    	            $connection->update($this->getTable(), $data, $condition);
                }
                else {
    	            $connection->delete($this->getTable(), $condition);
                }
            }
            $object->setData($this->getAttribute()->getName(), array());
        }
        return parent::beforeSave($object);
    }

    public function afterSave($object)
    {
    	$storeId       = $object->getStoreId();
        $attributeId   = $this->getAttribute()->getId();
        $entityId	   = $object->getId();
        $entityIdField = $this->getEntityIdField();
        $entityTypeId  = $this->getAttribute()->getEntity()->getTypeId();

        $connection = $this->getConnection('write');

        $values = $object->getData($this->getAttribute()->getName());

        if(isset($values['position']))
        {
            foreach ((array)$values['position'] as $valueId => $position) {
                if ($valueId >= 0) {
    	            $condition = array($connection->quoteInto('value_id = ?', $valueId));
                    $data = array();
                    $data['position'] = $position;
    	            $connection->update($this->getTable(), $data, $condition);
                    $valueIds[$valueId] = $valueId;
                }
                else {
                    $data = array();
    		        $data[$entityIdField] 	= $entityId;
    		        $data['attribute_id'] 	= $attributeId;
    		        $data['store_id']	  	= $storeId;
    		        $data['position']		= $position;
    		        $data['entity_type_id'] = $entityTypeId;
    	            $connection->insert($this->getTable(), $data);
                    $valueIds[$valueId] = $connection->lastInsertId();
                }

                unset($uploadedFileName);

                $types = $this->getImageTypes();
                foreach ($types as  $type) {
                    try {
                        $uploader = new Varien_File_Uploader($this->getAttribute()->getName().'_'.$type.'['.$valueId.']');
                        $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
                    }
                    catch (Exception $e){
                        continue;
                    }
                    if ($this->getAttribute()->getEntity()->getStoreId() == 0) {
                        $path = Mage::app()->getStore()->getConfig('system/filesystem/upload');
                    }
                    else {
                        $path = $this->getAttribute()->getEntity()->getStore()->getConfig('system/filesystem/upload');
                    }
                    $uploader->save($path.'/'.$type.'/', 'image_'.$entityId.'_'.$valueIds[$valueId].'.'.'jpg');
    	            if (!isset($uploadedFileName)) {
                        $uploadedFileName = $uploader->getUploadedFileName();
                    }
    	        }

                if (isset($uploadedFileName)) {
    	            $condition = array(
    		            $connection->quoteInto('value_id = ?', $valueIds[$valueId])
    	            );
                    $data = array();
    		        $data['value']		  	= $uploadedFileName;
    	            $connection->update($this->getTable(), $data, $condition);
                }
                else {
                    if ($valueId<0) {
                        $values['delete'][] = $valueIds[$valueId];
                    }
                }
            }
        }

        if(isset($values['delete']))
        {
            foreach ((array)$values['delete'] as $valueId) {
                if ($valueId != '') {
    	            $condition = array(
    		            $connection->quoteInto('value_id = ?', $valueId)
    	            );
    	            $connection->delete($this->getTable(), $condition);
                }
    	    }
        }

        return parent::afterSave($object);
    }

    public function getImageTypes()
    {
        return $this->_imageTypes;
    }

}
