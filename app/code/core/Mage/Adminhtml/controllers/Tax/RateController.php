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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml tax rate controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */

class Mage_Adminhtml_Tax_RateController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Show Main Grid
     *
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb(Mage::helper('tax')->__('Tax Rates'), Mage::helper('tax')->__('Tax Rates'))
            ->_addContent(
                $this->getLayout()->createBlock('adminhtml/tax_rate_toolbar_add', 'tax_rate_toolbar')
                    ->assign('createUrl', $this->getUrl('*/tax_rate/add'))
                    ->assign('header', Mage::helper('tax')->__('Tax Rates'))
            )
            ->_addContent($this->getLayout()->createBlock('adminhtml/tax_rate_grid', 'tax_rate_grid'))
            ->renderLayout();
    }

    /**
     * Show Add Form
     *
     */
    public function addAction()
    {
        $rateModel = Mage::getSingleton('tax/rate')
            ->load(null);
        $this->_initAction()
            ->_addBreadcrumb(Mage::helper('tax')->__('Tax Rates'), Mage::helper('tax')->__('Tax Rates'), $this->getUrl('*/tax_rate'))
            ->_addBreadcrumb(Mage::helper('tax')->__('New Tax Rate'), Mage::helper('tax')->__('New Tax Rate'))
            ->_addContent(
                $this->getLayout()->createBlock('adminhtml/tax_rate_toolbar_save')
                ->assign('header', Mage::helper('tax')->__('Add New Tax Rate'))
                ->assign('form', $this->getLayout()->createBlock('adminhtml/tax_rate_form'))
            )
            ->renderLayout();
    }

    /**
     * Save Rate and Data
     *
     * @return bool
     */
    public function saveAction()
    {
        if ($ratePost = $this->getRequest()->getPost()) {
            $ratePostData = $this->getRequest()->getPost('rate_data');

            $rateModel = Mage::getModel('tax/rate')->setData($ratePost);
            /* @var $rateModel Mage_Tax_Model_Rate */
            foreach ($ratePostData as $rateDataTypeId => $rateDataValue) {
                $rateModel->addRateData(array(
                    'rate_type_id'  => $rateDataTypeId,
                    'rate_value'    => $rateDataValue
                ));
            }

            try {
                $rateModel->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('tax')->__('Tax rate was successfully saved'));
                $this->getResponse()->setRedirect($this->getUrl("*/*/"));
                return true;
            }
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Error while saving this rate. Please try again later.'));
            }

            $this->_redirectReferer();
        }
    }

    /**
     * Show Edit Form
     *
     */
    public function editAction()
    {
        $rateId = (int)$this->getRequest()->getParam('rate');
        $rateModel = Mage::getSingleton('tax/rate')
            ->load($rateId);
        if (!$rateModel->getId()) {
            $this->getResponse()->setRedirect($this->getUrl("*/*/"));
            return ;
        }

        $this->_initAction()
            ->_addBreadcrumb(Mage::helper('tax')->__('Tax Rates'), Mage::helper('tax')->__('Tax Rates'), $this->getUrl('*/tax_rate'))
            ->_addBreadcrumb(Mage::helper('tax')->__('Edit Tax Rate'), Mage::helper('tax')->__('Edit Tax Rate'))
            ->_addContent(
                $this->getLayout()->createBlock('adminhtml/tax_rate_toolbar_save')
                ->assign('header', Mage::helper('tax')->__('Edit Tax Rate'))
                ->assign('form', $this->getLayout()->createBlock('adminhtml/tax_rate_form'))
            )
            ->renderLayout();
    }

    /**
     * Delete Rate and Data
     *
     * @return bool
     */
    public function deleteAction()
    {
        if ($rateId = $this->getRequest()->getParam('rate')) {
            $rateModel = Mage::getModel('tax/rate')->load($rateId);
            if ($rateModel->getId()) {
                try {
                    $rateModel->delete();

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('tax')->__('Tax rate was successfully deleted'));
                    $this->getResponse()->setRedirect($this->getUrl("*/*/"));
                    return true;
                }
                catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
                catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Error while deleting this rate. Please try again later.'));
                }
                if ($referer = $this->getRequest()->getServer('HTTP_REFERER')) {
                    $this->getResponse()->setRedirect($referer);
                }
                else {
                    $this->getResponse()->setRedirect($this->getUrl("*/*/"));
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Error while deleting this rate. Incorrect rate ID'));
                $this->getResponse()->setRedirect($this->getUrl('*/*/'));
            }
        }
    }

    /**
     * Export rates grid to CSV format
     *
     */
    public function exportCsvAction()
    {
        $fileName   = 'rates.csv';
        $content    = $this->getLayout()->createBlock('adminhtml/tax_rate_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export rates grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'rates.xml';
        $content    = $this->getLayout()->createBlock('adminhtml/tax_rate_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Initialize action
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/tax_rates')
            ->_addBreadcrumb(Mage::helper('tax')->__('Sales'), Mage::helper('tax')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('tax')->__('Tax'), Mage::helper('tax')->__('Tax'));
        return $this;
    }

    /**
     * Import and export Page
     *
     */
    public function importExportAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/tax_importExport')
            ->_addContent($this->getLayout()->createBlock('adminhtml/tax_rate_importExport'))
            ->renderLayout();
    }

    /**
     * import action from import/export tax
     *
     */
    public function importPostAction()
    {
        if ($this->getRequest()->isPost() && !empty($_FILES['import_rates_file']['tmp_name'])) {
            try {
                $this->_importRates();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('tax')->__('Tax rate was successfully imported'));
            }
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Invalid file upload attempt'));
            }
        }
        else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Invalid file upload attempt'));
        }
        $this->_redirect('*/*/importExport');
    }

    protected function _importRates()
    {
        $fileName   = $_FILES['import_rates_file']['tmp_name'];
        $csvObject  = new Varien_File_Csv();
        $csvData = $csvObject->getData($fileName);

        /** checks columns */
        $csvFields  = array(
            0   => Mage::helper('tax')->__('Country'),
            1   => Mage::helper('tax')->__('State'),
            2   => Mage::helper('tax')->__('Zip/Post Code')
        );

        $rateTypeI = 3;
        $rateTypes = array();
        $rateTypeCollection = Mage::getModel('tax/rate_type')->getCollection();
        foreach ($rateTypeCollection as $type) {
            $csvFields[$rateTypeI] = $type->getTypeName();
            $rateTypes[$rateTypeI] = $type->getId();
            $rateTypeI ++;
        }

        $regions = array();

        if ($csvData[0] == $csvFields) {
            Mage::getModel('tax/rate')->deleteAllRates();

            foreach ($csvData as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                if (count($csvFields) != count($v)) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Invalid file upload attempt'));
                    
                }

                if (empty($v[0])) {
                    Mage::throwException(Mage::helper('tax')->__('One of row has invalid country code.'));
                }

                if (!isset($regions[$v[0]])) {
                    $regionCollection = Mage::getModel('directory/region')->getCollection()
                        ->addCountryFilter($v[0]);
                    if ($regionCollection->getSize()) {
                        foreach ($regionCollection as $region) {
                            $regions[$v[0]][$region->getCode()] = $region->getRegionId();
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('One of row has invalid country code.'));
                    }
                }

                $rateData  = array(
                    'tax_country_id' => $v[0],
                    'tax_region_id' => $regions[$v[0]][$v[1]],
                    'tax_postcode'  => (empty($v[2]) || $v[2]=='*') ? null : $v[2]
                );

                $rateModel = Mage::getModel('tax/rate')
                    ->setData($rateData);
                foreach ($rateTypes as $i => $typeId) {
                    $rateModel->addRateData(array(
                        'rate_type_id'  => $typeId,
                        'rate_value'    => $v[$i]
                    ));
                }
                $rateModel->save();
            }
        }
        else {
            Mage::throwException(Mage::helper('tax')->__('Invalid file format upload attempt'));
        }
    }

    /**
     * export action from import/export tax
     *
     */
    public function exportPostAction()
    {
        /** get rate types */
        $rateTypes      = array();
        $rateTypeCollection = Mage::getModel('tax/rate_type')->getCollection();
        foreach ($rateTypeCollection as $type) {
            $rateTypes[$type->getId()] = $type->getTypeName();
        }

        /** start csv content and set template */
        $content    = '"'.Mage::helper('tax')->__('Country').'","'.Mage::helper('tax')->__('State').'","'.Mage::helper('tax')->__('Zip/Post Code').'"';
        $template   = '"{{country_name}}","{{region_name}}","{{tax_postcode}}"';
        foreach ($rateTypes as $k => $v) {
            $content   .= ',"'.$v.'"';
            $template  .= ',"{{rate_value_'.$k.'}}"';
        }
        $content .= "\n";

        $rateCollection = Mage::getModel('tax/rate')->getCollection()
            ->joinTypeData()
            ->joinCountryTable()
            ->joinRegionTable();
        foreach ($rateCollection as $rate) {
            $content .= $rate->toString($template)."\n";
        }

        $fileName = 'tax_rates.csv';

        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {

        switch ($this->getRequest()->getActionName()) {
            case 'importExport':
                return Mage::getSingleton('admin/session')->isAllowed('sales/tax/import_export');
                break;
            case 'index':
                return Mage::getSingleton('admin/session')->isAllowed('sales/tax/rates');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('sales/tax/rates');
                break;
        }
    }
}
