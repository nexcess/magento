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
 * Customer resource setup model
 *
 * @category   Mage
 * @package    Mage_Customer
 */
class Mage_Customer_Model_Entity_Setup extends Mage_Eav_Model_Entity_Setup
{

    public function getDefaultEntities()
    {
        return array(
            'customer' => array(
                'entity_model'          =>'customer/customer',
                'table'                 => 'customer/entity',
                'increment_model'       => 'eav/entity_increment_numeric',
                'increment_per_store'   => false,
                'attributes' => array(
//                    'entity_id'         => array('type'=>'static'),
//                    'entity_type_id'    => array('type'=>'static'),
//                    'attribute_set_id'  => array('type'=>'static'),
//                    'increment_id'      => array('type'=>'static'),
//                    'created_at'        => array('type'=>'static'),
//                    'updated_at'        => array('type'=>'static'),
//                    'is_active'         => array('type'=>'static'),

                    'website_id' => array(
                        'type'          => 'static',
                        'label'         => 'Associate to Website',
                        'input'         => 'select',
                        'source'        => 'customer/customer_attribute_source_website',
                        'backend'       => 'customer/customer_attribute_backend_website',
                        'sort_order'    => 1,
                    ),
                    'store_id' => array(
                        'type'          => 'static',
                        'label'         => 'Create In',
                        'input'         => 'select',
                        'source'        => 'customer/customer_attribute_source_store',
                        'backend'       => 'customer/customer_attribute_backend_store',
                        'visible'       => false,
                        'sort_order'    => 2,
                    ),
                    'created_in' => array(
                        'type'          => 'varchar',
                        'label'         => 'Created From',
                        'sort_order'    => 3,
                    ),
                    'firstname' => array(
                        'label'         => 'First Name',
                        'sort_order'    => 4,
                    ),
                    'lastname' => array(
                        'label'         => 'Last Name',
                        'sort_order'    => 5,
                    ),
                    'email' => array(
                        'type'          => 'static',
                        'label'         => 'Email',
                        'class'         => 'validate-email',
                        'sort_order'    => 6,
                    ),
                    'group_id' => array(
                        'type'          => 'static',
                        'input'         => 'select',
                        'label'         => 'Customer Group',
                        'source'        => 'customer/customer_attribute_source_group',
                        'sort_order'    => 7,
                    ),
                    'password_hash' => array(
                        'input'         => 'hidden',
                        'backend'       => 'customer/customer_attribute_backend_password',
                        'required'      => false,
                    ),
                    'default_billing' => array(
                        'type'          => 'int',
                        'visible'       => false,
                        'required'      => false,
                        'backend'       => 'customer/customer_attribute_backend_billing',
                    ),
                    'default_shipping' => array(
                        'type'          => 'int',
                        'visible'       => false,
                        'required'      => false,
                        'backend'       => 'customer/customer_attribute_backend_shipping',
                    ),
                ),
            ),

            'customer_address'=>array(
                'entity_model'  =>'customer/customer_address',
                'table' => 'customer/address_entity',
                'attributes' => array(
//                    'entity_id'         => array('type'=>'static'),
//                    'entity_type_id'    => array('type'=>'static'),
//                    'attribute_set_id'  => array('type'=>'static'),
//                    'increment_id'      => array('type'=>'static'),
//                    'parent_id'         => array('type'=>'static'),
//                    'created_at'        => array('type'=>'static'),
//                    'updated_at'        => array('type'=>'static'),
//                    'is_active'         => array('type'=>'static'),

                    'firstname' => array(
                        'label'         => 'First Name',
                        'sort_order'    => 1,
                    ),
                    'lastname' => array(
                        'label'         => 'Last Name',
                        'sort_order'    => 2,
                    ),
                    'company' => array(
                        'label'         => 'Company',
                        'required'      => false,
                        'sort_order'    => 3,
                    ),
                    'street' => array(
                        'type'          => 'text',
                        'backend'       => 'customer_entity/address_attribute_backend_street',
                        'input'         => 'multiline',
                        'label'         => 'Street Address',
                        'sort_order'    => 4,
                    ),
                    'city' => array(
                        'label'         => 'City',
                        'sort_order'    => 5,
                    ),
                    'country_id' => array(
                        'type'          => 'varchar',
                        'input'         => 'select',
                        'label'         => 'Country',
                        'class'         => 'countries',
                        'source'        => 'customer_entity/address_attribute_source_country',
                        'sort_order'    => 6,
                    ),
                    'region' => array(
                        'backend'       => 'customer_entity/address_attribute_backend_region',
                        'label'         => 'State/Province',
                        'class'         => 'regions',
                        'sort_order'    => 7,
                    ),
                    'region_id' => array(
                        'type'          => 'int',
                        'input'         => 'hidden',
                        'source'        => 'customer_entity/address_attribute_source_region',
                        'required'      => 'false',
                        'sort_order'    => 8,
                    ),
                    'postcode' => array(
                        'label'         => 'Zip/Postal Code',
                        'sort_order'    => 9,
                    ),
                    'telephone' => array(
                        'label'         => 'Telephone',
                        'sort_order'    => 10,
                    ),
                    'fax' => array(
                        'label'         => 'Fax',
                        'required'      => false,
                        'sort_order'    => 11,
                    ),
                ),
            ),
        );
    }

}
