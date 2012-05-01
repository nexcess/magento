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
 * Customer model
 *
 */
class Mage_Customer_Model_Customer extends Mage_Core_Model_Abstract
{
    const XML_PATH_REGISTER_EMAIL_TEMPLATE  = 'customer/create_account/email_template';
    const XML_PATH_REGISTER_EMAIL_IDENTITY  = 'customer/create_account/email_identity';
    const XML_PATH_FORGOT_EMAIL_TEMPLATE    = 'customer/password/forgot_email_template';
    const XML_PATH_FORGOT_EMAIL_IDENTITY    = 'customer/password/forgot_email_identity';
    const XML_PATH_DEFAULT_EMAIL_DOMAIN     = 'customer/create_account/email_domain';

    const SUBSCRIBED_YES = 'yes';
    const SUBSCRIBED_NO = 'no';

    protected $_eventPrefix = 'customer';
    protected $_eventObject = 'customer';

    protected $_addresses = null;

    protected $_errors    = array();

    function _construct()
    {
        $this->_init('customer/customer');
    }

    /**
     * Retrieve customer sharing configuration model
     *
     * @return unknown
     */
    public function getSharingConfig()
    {
        return Mage::getSingleton('customer/config_share');

    }

    /**
     * Authenticate customer
     *
     * @param   string $login
     * @param   string $password
     * @return  Mage_Customer_Model_Customer || false
     */
    public function authenticate($login, $password)
    {
        return $this->loadByEmail($login)->validatePassword($password);
    }

    /**
     * Load customer by email
     *
     * @param   string $customerEmail
     * @return  Mage_Customer_Model_Customer
     */
    public function loadByEmail($customerEmail)
    {
        $this->_getResource()->loadByEmail($this, $customerEmail);
        return $this;
    }


    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        $storeId = $this->getStoreId();
        if (is_null($storeId)) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }

        $this->getGroupId();
        return $this;
    }

    /**
     * Change customer password
     * $data = array(
     *      ['password']
     *      ['confirmation']
     *      ['current_password']
     * )
     *
     * @param   array $data
     * @param   bool $checkCurrent
     * @return  this
     */
    public function changePassword($newPassword, $checkCurrent=true)
    {
        $this->_getResource()->changePassword($this, $newPassword, $checkCurrent);
        return $this;
    }

    /**
     * Get full customer name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    /**
     * Add address to address collection
     *
     * @param   Mage_Customer_Model_Address $address
     * @return  Mage_Customer_Model_Customer
     */
    public function addAddress(Mage_Customer_Model_Address $address)
    {
        $this->getAddresses();
        $this->_addresses[] = $address;
        return $this;
    }

    /**
     * Retrieve customer address by address id
     *
     * @param   int $addressId
     * @return  Mage_Customer_Model_Address
     */
    public function getAddressById($addressId)
    {
        return Mage::getModel('customer/address')
            ->load($addressId);
    }

    /**
     * Retrieve not loaded address collection
     *
     * @return Mage_Customer_Model_Address_Collection
     */
    public function getAddressCollection()
    {
        return Mage::getResourceModel('customer/address_collection');
    }

    /**
     * Retrieve customer address array
     *
     * @return array
     */
    public function getAddresses()
    {
        if (is_null($this->_addresses)) {
            $this->_addresses = array();
            $collection = $this->getAddressCollection()
                ->setCustomerFilter($this)
                ->addAttributeToSelect('*')
                ->load();
            foreach ($collection as $address) {
                $this->_addresses[] = $address;
            }
        }

        return $this->_addresses;
    }

    /**
     * Retrieve all customer attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_getResource()
            ->loadAllAttributes($this)
            ->getAttributesByCode();
    }

    /**
     * Set plain and hashed password
     *
     * @param string $password
     * @return Mage_Customer_Model_Customer
     */
    public function setPassword($password)
    {
        $this->setData('password', $password);
        $this->setPasswordHash($this->hashPassword($password));
        return $this;
    }

    /**
     * Hach customer password
     *
     * @param   string $password
     * @return  string
     */
    public function hashPassword($password, $salt=null)
    {
        return Mage::helper('core')->getHash($password, !is_null($salt) ? $salt : 2);
    }

    /**
     * Retrieve random password
     *
     * @param   int $length
     * @return  string
     */
    public function generatePassword($length=6)
    {
        return substr(md5(uniqid(rand(), true)), 0, $length);
    }

    /**
     * Validate password with salted hash
     *
     * @param string $password
     * @return boolean
     */
    public function validatePassword($password)
    {
        if (!($hash = $this->getPasswordHash())) {
            return false;
        }
        return Mage::helper('core')->validateHash($password, $hash);
    }

    /**
     * Encrypt password
     *
     * @param   string $password
     * @return  string
     */
    public function encryptPassword($password)
    {
        return Mage::helper('core')->encrypt($password);
    }

    /**
     * Decrypt password
     *
     * @param   string $password
     * @return  string
     */
    public function decryptPassword($password)
    {
        return Mage::helper('core')->decrypt($password);
    }

    /**
     * Retrieve primary address by type(attribute)
     *
     * @param   string $attributeCode
     * @return  Mage_Customer_Mode_Address
     */
    public function getPrimaryAddress($attributeCode)
    {
        $addressId = $this->getData($attributeCode);
        $primaryAddress = false;
        if ($addressId) {
            foreach ($this->getAddresses() as $address) {
                if ($addressId == $address->getId()) {
                    return $address;
                }
            }
        }
        return $primaryAddress;
    }

    /**
     * Retrieve customer primary billing address
     *
     * @return Mage_Customer_Mode_Address
     */
    public function getPrimaryBillingAddress()
    {
        return $this->getPrimaryAddress('default_billing');
    }

    public function getDefaultBillingAddress()
    {
        return $this->getPrimaryBillingAddress();
    }

    /**
     * Retrieve primary customer shipping address
     *
     * @return Mage_Customer_Mode_Address
     */
    public function getPrimaryShippingAddress()
    {
        return $this->getPrimaryAddress('default_shipping');
    }

    public function getDefaultShippingAddress()
    {
        return $this->getPrimaryShippingAddress();
    }

    /**
     * Retrieve ids of primary addresses
     *
     * @return unknown
     */
    public function getPrimaryAddressIds()
    {
        $ids = array();
        if ($this->getDefaultBilling()) {
            $ids[] = $this->getDefaultBilling();
        }
        if ($this->getDefaultShipping()) {
            $ids[] = $this->getDefaultShipping();
        }
        return $ids;
    }

    /**
     * Retrieve all customer primary addresses
     *
     * @return array
     */
    public function getPrimaryAddresses()
    {
        $addresses = array();
        $primaryBilling = $this->getPrimaryBillingAddress();
        if ($primaryBilling) {
            $addresses[] = $primaryBilling;
            $primaryBilling->setIsPrimaryBilling(true);
        }

        $primaryShipping = $this->getPrimaryShippingAddress();
        if ($primaryShipping) {
            if ($primaryBilling->getId() == $primaryShipping->getId()) {
                $primaryBilling->setIsPrimaryShipping(true);
            }
            else {
                $primaryShipping->setIsPrimaryShipping(true);
                $addresses[] = $primaryShipping;
            }
        }
        return $addresses;
    }

    /**
     * Retrieve not primary addresses
     *
     * @return array
     */
    public function getAdditionalAddresses()
    {
        $addresses = array();
        $primatyIds = $this->getPrimaryAddressIds();
        foreach ($this->getAddresses() as $address) {
            if (!in_array($address->getId(), $primatyIds)) {
                $addresses[] = $address;
            }
        }
        return $addresses;
    }

    public function isAddressPrimary(Mage_Customer_Model_Address $address)
    {
        if (!$address->getId()) {
            return false;
        }
        return ($address->getId() == $this->getDefaultBilling()) || ($address->getId() == $this->getDefaultShipping());
    }

    /**
     * Send email with account information
     *
     * @return Mage_Customer_Model_Customer
     */
    public function sendNewAccountEmail()
    {
        Mage::getModel('core/email_template')
            ->setDesignConfig(array('area'=>'frontend', 'store'=>$this->getStoreId()))
            ->sendTransactional(
                Mage::getStoreConfig(self::XML_PATH_REGISTER_EMAIL_TEMPLATE),
                Mage::getStoreConfig(self::XML_PATH_REGISTER_EMAIL_IDENTITY),
                $this->getEmail(),
                $this->getName(),
                array('customer'=>$this));
        return $this;
    }

    /**
     * Send email with new customer password
     *
     * @return Mage_Customer_Model_Customer
     */
    public function sendPasswordReminderEmail()
    {
        Mage::getModel('core/email_template')
            ->sendTransactional(
              Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_TEMPLATE),
              Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_IDENTITY),
              $this->getEmail(),
              $this->getName(),
              array('customer'=>$this));
        return $this;
    }

    /**
     * Retrieve customer group identifier
     *
     * @return int
     */
    public function getGroupId()
    {
        if (!$this->getData('group_id')) {
            $storeId = $this->getStoreId() ? $this->getStoreId() : Mage::app()->getStore()->getId();
            $this->setData('group_id', Mage::getStoreConfig(Mage_Customer_Model_Group::XML_PATH_DEFAULT_ID, $storeId));
        }
        return $this->getData('group_id');
    }

    /**
     * Retrieve customer tax class identifier
     *
     * @return int
     */
    public function getTaxClassId()
    {
        if (!$this->getData('tax_class_id')) {
            $this->setTaxClassId(Mage::getModel('customer/group')->getTaxClassId($this->getGroupId()));
        }
        return $this->getData('tax_class_id');
    }

    /**
     * Check store availability for customer
     *
     * @param   mixed $store
     * @return  bool
     */
    public function isInStore($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $storeId = $store->getId();
        }
        else {
            $storeId = $store;
        }
        $availableStores = $this->getSharedStoreIds();
        return in_array($storeId, $availableStores);
    }

    /**
     * Retrieve store where customer was created
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore($this->getStoreId());
    }

    /**
     * Retrieve shared store ids
     *
     * @return array|false
     */
    public function getSharedStoreIds()
    {
        return $this->getStore()->getWebsite()->getStoresIds();
    }

    /**
     * Enter description here...
     *
     * @param Mage_Core_Model_Store $store
     * @return Mage_Customer_Model_Customer
     */
    public function setStore(Mage_Core_Model_Store $store)
    {
        $this->setStoreId($store->getId());
        return $this;
    }

    /**
     * Validate customer attribute values
     *
     * @return bool
     */
    public function validate()
    {
        $errors = array();
        $helper = Mage::helper('customer');

        if (!Zend_Validate::is($this->getFirstname(), 'NotEmpty')) {
            $errors[] = $helper->__('First name can\'t be empty');
        }

        if (!Zend_Validate::is($this->getLastname(), 'NotEmpty')) {
            $errors[] = $helper->__('Last name can\'t be empty');
        }

        if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $errors[] = $helper->__('Invalid email address');
        }

        $password = $this->getPassword();
        if (!$this->getId() && !Zend_Validate::is($password, 'NotEmpty')) {
            $errors[] = $helper->__('Password can\'t be empty');
        }
        if ($password && !Zend_Validate::is($password, 'StringLength', array(6))) {
            $errors[] = $helper->__('Password minimal length must be more %s', 6);
        }
        $confirmation = $this->getConfirmation();
        if ($password != $confirmation) {
            $errors[] = $helper->__('Please make sure your passwords match.');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Importing customer data from text array
     *
     * @param array $row
     * @return uMage_Customer_Model_Customer
     */
    public function importFromTextArray(array $row)
    {
        $this->resetErrors();
        $hlp = Mage::helper('customer');
        $line = $row['i'];
        $row = $row['row'];

        $regions = Mage::getResourceModel('directory/region_collection');
//        $config = Mage::getSingleton('eav/config')->getEntityType('customer');

        $website = Mage::getModel('core/website')->load($row['website_code'], 'code');

        if (!$website->getId()) {
            $this->addError($hlp->__('Invalid website, skipping the record, line: %s', $line));

        } else {
            $row['website_id'] = $website->getWebsiteId();
            $this->setWebsiteId($row['website_id']);
        }

        // Validate Email
        if (empty($row['email'])) {
            $this->addError($hlp->__('Missing email, skipping the record, line: %s', $line));
        } else {
            $this->loadByEmail($row['email']);
        }

        if (empty($row['entity_id'])) {
            if ($this->getData('entity_id')) {
                $this->addError($hlp->__('Customer email (%s) already exists, skipping the record , line: %s', $row['email'], $line));
            }
        } else {
            if ($row['entity_id'] != $this->getData('entity_id')) {
                $this->addError($hlp->__('CustomerID and email didn\'t match, skipping the record , line: %s', $line));
            } else {
                $this->unsetData();
                $this->load($row['entity_id']);
                if (isset($row['store_view'])) {
                    $storeId = Mage::app()->getStore($row['store_view'])->getId();
                    if ($storeId) $this->setStoreId($storeId);
                }
            }
        }

        if (empty($row['website_code'])) {
            $this->addError($hlp->__('Missing website, skipping the record, line: %s', $line));
        }

        if (empty($row['group'])) {
            $row['group'] = 'General';
        }

        if (empty($row['firstname'])) {
            $this->addError($hlp->__('Missing firstname, skipping the record, line: %s', $line));
        }
        if (empty($row['lastname'])) {
            $this->addError($hlp->__('Missing lastname, skipping the record, line: %s', $line));
        }

        if (!empty($row['password_new'])) {
            $this->setPassword($row['password_new']);
            unset($row['password_new']);
            if (!empty($row['password_hash'])) unset($row['password_hash']);
        }

        if ($errors = $this->getErrors()) {
            $this->unsetData();
            $this->printError(join("<br />",$errors));
            return;
        }
//        $entity = $this->getResource();
        foreach ($row as $field=>$value) {

//            $attribute = $entity->getAttribute($field);
//            if (!$attribute) {
//                echo $field;
//                continue;
//            }
//            if ($attribute->usesSource()) {
//                $source = $attribute->getSource();
//                $optionId = $config->getSourceOptionId($source, $value);
//                if (is_null($optionId)) {
//                    $this->printError($hlp->__("Invalid attribute option specified for attribute attribute %s (%s)", $field, $value), $line);
//                }
//                $value = $optionId;
//            }

            $this->setData($field, $value);
        }

        if (!$this->validateAddress($row, 'billing')) {
            $this->printError($hlp->__('Invalid billing address for (%s)', $row['email']), $line);
        } else {
            // Handling billing address
            $billingAddress = $this->getPrimaryBillingAddress();
            if (!$billingAddress  instanceof Mage_Customer_Model_Address) {
                $billingAddress = new Mage_Customer_Model_Address();
            }

            $regions->addRegionNameFilter($row['billing_region'])->load();
            if ($regions) foreach($regions as $region) {
                $regionId = $region->getId();
            }

            $billingAddress->setFirstname($row['firstname']);
            $billingAddress->setLastname($row['lastname']);
            $billingAddress->setCity($row['billing_city']);
            $billingAddress->setRegion($row['billing_region']);
            if (isset($regionId)) $billingAddress->setRegionId($regionId);
            $billingAddress->setCountryId($row['billing_country']);
            $billingAddress->setPostcode($row['billing_postcode']);
            if (isset($row['billing_street2'])) {
                $billingAddress->setStreet(array($row['billing_street1'],$row['billing_street2']));
            } else {
                $billingAddress->setStreet(array($row['billing_street1']));
            }
            if (isset($row['billing_telephone'])) {
                $billingAddress->setTelephone($row['billing_telephone']);
            }

            if (!$billingAddress->getId()) {
                $billingAddress->setIsDefaultBilling(true);
                if ($this->getDefaultBilling()) {
                    $this->setData('default_billing', '');
                }
                $this->addAddress($billingAddress);
            } // End handling billing address
        }

        if (!$this->validateAddress($row, 'shipping')) {
            $this->printError($hlp->__('Invalid shipping address for (%s)', $row['email']), $line);
        } else {
            // Handling shipping address
            $shippingAddress = $this->getPrimaryShippingAddress();
            if (!$shippingAddress instanceof Mage_Customer_Model_Address) {
                $shippingAddress = new Mage_Customer_Model_Address();
            }

            $regions->addRegionNameFilter($row['shipping_region'])->load();

            if ($regions) foreach($regions as $region) {
               $regionId = $region->getId();
            }

            $shippingAddress->setFirstname($row['firstname']);
            $shippingAddress->setLastname($row['lastname']);
            $shippingAddress->setCity($row['shipping_city']);
            $shippingAddress->setRegion($row['shipping_region']);
            if (isset($regionId)) $shippingAddress->setRegionId($regionId);
            $shippingAddress->setCountryId($row['shipping_country']);
            $shippingAddress->setPostcode($row['shipping_postcode']);
            if (isset($row['shipping_street2'])) {
                $shippingAddress->setStreet(array($row['shipping_street1'], $row['shipping_street2']));
            } else {
                $shippingAddress->setStreet(array($row['shipping_street1']));
            }
            if (!empty($row['shipping_telephone'])) {
                $shippingAddress->setTelephone($row['shipping_telephone']);
            }

            if (!$shippingAddress->getId()) {
               $shippingAddress->setIsDefaultShipping(true);
               $this->addAddress($shippingAddress);
            }
            // End handling shipping address
        }
        if (!empty($row['is_subscribed'])) {
            $this->setIsSubscribed(strtolower($row['is_subscribed'])==self::SUBSCRIBED_YES ? 1 : 0);
        }
        unset($row);
        return $this;
    }

    function unsetSubscription()
    {
        if (isset($this->_isSubscribed)) {
            unset($this->_isSubscribed);
        }
    }

    function cleanAllAddresses() {
        $this->_addresses = null;
    }

    function addError($error)
    {
        $this->_errors[] = $error;
    }

    function getErrors()
    {
        return $this->_errors;
    }

    function resetErrors()
    {
        $this->_errors = array();
    }

    function printError($error, $line = null)
    {
        if ($error == null) return false;
        $img = 'error_msg_icon.gif';
        $liStyle = 'background-color:#FDD; ';
        echo '<li style="'.$liStyle.'">';
        echo '<img src="'.Mage::getDesign()->getSkinUrl('images/'.$img).'" class="v-middle"/>';
        echo $error;
        if ($line) {
            echo '<small>, Line: <b>'.$line.'</b></small>';
        }
        echo "</li>";
    }

    function validateAddress(array $data, $type = 'billing')
    {
        $fields = array('city',
            'country', 'postcode',
            'telephone', 'street1');
        $usca = array('US', 'CA');
        $prefix = $type ? $type.'_':'';

        if ($data) {
            foreach($fields as $field) {
                if (!isset($data[$prefix.$field])) {
                    return false;
                }
                if ($field == 'country'
                    && in_array(strtolower($data[$prefix.$field]), array('US', 'CA'))) {

                    if (!isset($data[$prefix.'region'])) {
                        return false;
                    }

                    $region = Mage::getModel('directory/region')
                        ->loadByName($data[$prefix.'region']);
                    if (!$region->getId()) {
                        return false;
                    }
                    unset($region);
                }
            }
            unset($data);
            return true;
        }
        return false;
    }

}