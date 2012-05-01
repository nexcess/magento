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
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Quote address  model
 */
class Mage_Sales_Model_Quote_Address extends Mage_Customer_Model_Address_Abstract
{
    const RATES_FETCH = 1;
    const RATES_RECALCULATE = 2;

    /**
     * Quote object
     *
     * @var Mage_Sales_Model_Quote
     */
    protected $_items;
    protected $_quote;
    protected $_rates;
    protected $_totalModels;
    protected $_totals = array();

    protected function _construct()
    {
        $this->_init('sales/quote_address');
    }

    public function __destruct()
    {
        unset($this->_quote);
        unset($this->_rates);
        unset($this->_totalModels);
        unset($this->_totals);
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        if ($this->getQuote()) {
            $this->setParentId($this->getQuote()->getId());
        }
        return $this;
    }

    protected function _afterSave()
    {
        parent::_afterSave();
        $this->getItemsCollection()->save();
        $this->getShippingRatesCollection()->save();
        return $this;
    }

    /**
     * Declare adress quote model object
     *
     * @param   Mage_Sales_Model_Quote $quote
     * @return  Mage_Sales_Model_Quote_Address
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Retrieve quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * Import quote address data from customer address object
     *
     * @param   Mage_Customer_Model_Address $address
     * @return  Mage_Sales_Model_Quote_Address
     */
    public function importCustomerAddress(Mage_Customer_Model_Address $address)
    {
        $this->setCustomerAddressId($address->getId())
            ->setCustomerId($address->getParentId())
            ->setEmail($address->hasEmail() ? $address->getEmail() : $address->getCustomer()->getEmail())
            ->setFirstname($address->getFirstname())
            ->setLastname($address->getLastname())
            ->setCompany($address->getCompany())
            ->setStreet($address->getStreet())
            ->setCity($address->getCity())
            ->setRegion($address->getRegion())
            ->setRegionId($address->getRegionId())
            ->setPostcode($address->getPostcode())
            ->setCountryId($address->getCountryId())
            ->setTelephone($address->getTelephone())
            ->setFax($address->getFax())
        ;
        return $this;
    }

    /**
     * Export data to customer address object
     *
     * @return Mage_Customer_Model_Address
     */
    public function exportCustomerAddress()
    {
        $address = Mage::getModel('customer/address')
            ->setEmail($this->getEmail())
            ->setFirstname($this->getFirstname())
            ->setLastname($this->getLastname())
            ->setCompany($this->getCompany())
            ->setStreet($this->getStreet())
            ->setCity($this->getCity())
            ->setRegion($this->getRegion())
            ->setRegionId($this->getRegionId())
            ->setPostcode($this->getPostcode())
            ->setCountryId($this->getCountryId())
            ->setTelephone($this->getTelephone())
            ->setFax($this->getFax());

        return $address;
    }

    /**
     * Import address data from order address
     *
     * @param   Mage_Sales_Model_Order_Address $address
     * @return  Mage_Sales_Model_Quote_Address
     */
    public function importOrderAddress(Mage_Sales_Model_Order_Address $address)
    {
        $this->setAddressType($address->getAddressType())
            ->setCustomerId($address->getCustomerId())
            ->setCustomerAddressId($address->getCustomerAddressId())
            ->setEmail($address->getEmail())
            ->setFirstname($address->getFirstname())
            ->setLastname($address->getLastname())
            ->setCompany($address->getCompany())
            ->setStreet($address->getStreet())
            ->setCity($address->getCity())
            ->setRegion($address->getRegion())
            ->setRegionId($address->getRegionId())
            ->setPostcode($address->getPostcode())
            ->setCountryId($address->getCountryId())
            ->setTelephone($address->getTelephone())
            ->setFax($address->getFax())
        ;
        return $this;
    }

    /**
     * Convert object to array
     *
     * @param   array $arrAttributes
     * @return  array
     */
    public function toArray(array $arrAttributes = array())
    {
        $arr = parent::toArray();
        $arr['rates'] = $this->getShippingRatesCollection()->toArray($arrAttributes);
        $arr['items'] = $this->getItemsCollection()->toArray($arrAttributes);
        foreach ($this->getTotals() as $k=>$total) {
            $arr['totals'][$k] = $total->toArray();
        }
        return $arr;
    }

/*********************** ITEMS ***************************/

    /**
     * Retrieve address items collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getItemsCollection()
    {
        if (is_null($this->_items)) {
            $this->_items = Mage::getResourceModel('sales/quote_address_item_collection')
                ->addAttributeToSelect('*')
                ->setAddressFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_items as $item) {
                    $item->setAddress($this);
                }
            }
        }
        return $this->_items;
    }

    public function getAllItems()
    {
        $quoteItems = $this->getQuote()->getItemsCollection();
        $addressItems = $this->getItemsCollection();

        $items = array();
        if ($this->getQuote()->getIsMultiShipping() && $addressItems->count()>0) {
            foreach ($addressItems as $aItem) {
                if ($aItem->isDeleted()) {
                    continue;
                }

                if (!$aItem->getQuoteItemImported()) {
                    if ($qItem = $this->getQuote()->getItemById($aItem->getQuoteItemId())) {
                        $aItem->importQuoteItem($qItem);
                    }
                }
                $items[] = $aItem;
            }
        } else {
            foreach ($quoteItems as $qItem) {
                if ($qItem->isDeleted()) {
                    continue;
                }
                $items[] = $qItem;
            }
        }
        return $items;
    }

    public function getItemQty($itemId=0) {
        $qty = 0;
        if ($itemId == 0) {
            foreach ($this->getAllItems() as $item) {
                $qty += $item->getQty();
            }
        } else {
            if ($item = $this->getItemById($itemId)) {
                $qty = $item->getQty();
            }
        }
        return $qty;
    }

    public function hasItems()
    {
        return sizeof($this->getAllItems())>0;
    }

    public function getItemById($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getId()==$itemId) {
                return $item;
            }
        }
        return false;
    }

    public function getItemByQuoteItemId($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getQuoteItemId()==$itemId) {
                return $item;
            }
        }
        return false;
    }

    public function removeItem($itemId)
    {
        if ($item = $this->getItemById($itemId)) {
            $item->isDeleted(true);
        }
        return $this;
    }

    public function addItem(Mage_Sales_Model_Quote_Address_Item $item)
    {
        $item->setAddress($this)
            ->setParentId($this->getId());
        if (!$item->getId()) {
            $this->getItemsCollection()->addItem($item);
        }
        return $this;
    }


/*********************** SHIPPING RATES ***************************/

    /**
     * Retrieve collection of quote shipping rates
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getShippingRatesCollection()
    {
        if (is_null($this->_rates)) {
            $this->_rates = Mage::getResourceModel('sales/quote_address_rate_collection')
                ->addAttributeToSelect('*')
                ->setAddressFilter($this->getId());
            if ($this->getId()) {
                foreach ($this->_rates as $rate) {
                    $rate->setAddress($this);
                }
            }
        }
        return $this->_rates;
    }

    /**
     * Retrieve all address shipping rates
     *
     * @return array
     */
    public function getAllShippingRates()
    {
        $rates = array();
        foreach ($this->getShippingRatesCollection() as $rate) {
            if (!$rate->isDeleted()) {
                $rates[] = $rate;
            }
        }
        return $rates;
    }

    /**
     * Retrieve all grouped shipping rates
     *
     * @return array
     */
    public function getGroupedAllShippingRates()
    {
        $rates = array();
        foreach ($this->getShippingRatesCollection() as $rate) {
            if (!$rate->isDeleted() && $rate->getCarrierInstance()) {
                if (!isset($rates[$rate->getCarrier()])) {
                    $rates[$rate->getCarrier()] = array();
                }

                $rates[$rate->getCarrier()][] = $rate;
                $rates[$rate->getCarrier()][0]->carrier_sort_order = $rate->getCarrierInstance()->getSortOrder();
            }
        }
        uasort($rates, array($this, '_sortRates'));
        return $rates;
    }

    protected function _sortRates($a, $b)
    {
        return (int)$a[0]->carrier_sort_order < (int)$b[0]->carrier_sort_order ? -1 : ((int)$a[0]->carrier_sort_order > (int)$b[0]->carrier_sort_order ? 1 : 0);
    }

    /**
     * Retrieve shipping rate by identifier
     *
     * @param   int $rateId
     * @return  Mage_Sales_Model_Quote_Address_Rate | false
     */
    public function getShippingRateById($rateId)
    {
        foreach ($this->getShippingRatesCollection() as $rate) {
            if ($rate->getId()==$rateId) {
                return $rate;
            }
        }
        return false;
    }

    /**
     * Retrieve shipping rate by code
     *
     * @param   string $code
     * @return  Mage_Sales_Model_Quote_Address_Rate
     */
    public function getShippingRateByCode($code)
    {
        foreach ($this->getShippingRatesCollection() as $rate) {
            if ($rate->getCode()==$code) {
                return $rate;
            }
        }
        return false;
    }

    public function removeAllShippingRates()
    {
        foreach ($this->getShippingRatesCollection() as $rate) {
            $rate->isDeleted(true);
        }
        return $this;
    }

    public function addShippingRate(Mage_Sales_Model_Quote_Address_Rate $rate)
    {
        $rate->setAddress($this)
            ->setParentId($this->getId());
        $this->getShippingRatesCollection()->addItem($rate);
        return $this;
    }

    /**
     * Collecting shipping rates by address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function collectShippingRates()
    {
        if (!$this->getCollectShippingRates()) {
            return $this;
        }

        $this->setCollectShippingRates(false);

        $this->removeAllShippingRates();

        if (!$this->getCountryId() && !$this->getPostcode()) {
            return $this;
        }

        $request = Mage::getModel('shipping/rate_request');
        $request->setDestCountryId($this->getCountryId());
        $request->setDestRegionId($this->getRegionId());
        $request->setDestRegionCode($this->getRegionCode());
        /**
         * need to call getStreet with -1
         * to get data in string instead of array
         */
        $request->setDestStreet($this->getStreet(-1));
        $request->setDestCity($this->getCity());
        $request->setDestPostcode($this->getPostcode());
        $request->setPackageValue($this->getBaseSubtotal());
        $request->setPackageWeight($this->getWeight());
        $request->setPackageQty($this->getItemQty());

        $request->setFreeMethodWeight($this->getFreeMethodWeight());

        /**
         * Store and website identifiers need specify from quote
         */
        /*$request->setStoreId(Mage::app()->getStore()->getId());
        $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId());*/

        $request->setStoreId($this->getQuote()->getStore()->getId());
        $request->setWebsiteId($this->getQuote()->getStore()->getWebsiteId());
        $request->setFreeShipping($this->getFreeShipping());

        /**
         * Currencies need to convert in free shipping
         */
        $request->setBaseCurrency($this->getQuote()->getStore()->getBaseCurrency());
        $request->setPackageCurrency($this->getQuote()->getStore()->getCurrentCurrency());

        $result = Mage::getModel('shipping/shipping')
            ->collectRates($request)
                ->getResult();

        $found = false;
        if ($result) {
            $shippingRates = $result->getAllRates();

            foreach ($shippingRates as $shippingRate) {
                $rate = Mage::getModel('sales/quote_address_rate')
                    ->importShippingRate($shippingRate);
                $this->addShippingRate($rate);

                if ($this->getShippingMethod()==$rate->getCode()) {
                    $this->setShippingAmount($rate->getPrice());
                    $found = true;
                }
            }
        }

        if (!$found) {
            $this->setShippingAmount(0)
                ->setShippingMethod('')
                ->setShippingDescription('');
        }

        return $this;
    }

/*********************** TOTALS ***************************/
    public function getTotalModels()
    {
        if (!$this->_totalModels) {
            $totalsConfig = Mage::getConfig()->getNode('global/sales/quote/totals');
            $models = array();
            foreach ($totalsConfig->children() as $totalCode=>$totalConfig) {
                $sort = Mage::getStoreConfig('sales/totals_sort/'.$totalCode);
                while (isset($models[$sort])) {
                    $sort++;
                }
                $class = $totalConfig->getClassName();
                if ($class && ($model = Mage::getModel($class))) {
                    $models[$sort] = $model->setCode($totalCode);
                }
            }
            ksort($models);
            $this->_totalModels = $models;
        }
        return $this->_totalModels;
    }

    public function collectTotals()
    {
        foreach ($this->getTotalModels() as $model) {
            if (is_callable(array($model, 'collect'))) {
                $model->collect($this);
            }
        }
        return $this;
    }

    public function getTotals()
    {
        foreach ($this->getTotalModels() as $model) {
            if (is_callable(array($model, 'fetch'))) {
                $model->fetch($this);
            }
        }
        return $this->_totals;
    }

    public function addTotal($total)
    {
        if (is_array($total)) {
            $totalInstance = Mage::getModel('sales/quote_address_total')
                ->setData($total);
        } elseif ($total instanceof Mage_Sales_Model_Quote_Total) {
            $totalInstance = $total;
        }
        $this->_totals[$totalInstance->getCode()] = $totalInstance;
        return $this;
    }

    public function __clone()
    {
        $this->setEntityId(null);
    }

    protected function _beforeDelete()
    {
        parent::_beforeDelete();

        $this->getItemsCollection()->walk('delete');
        $this->getShippingRatesCollection()->walk('delete');
    }
}