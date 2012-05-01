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
 * Payment method abstract model
 *
 */
class Mage_Sales_Model_Order_Pdf_Shipment extends Mage_Sales_Model_Order_Pdf_Abstract
{
    public function getPdf($shipments = array())
    {
        $pdf = new Zend_Pdf();
        $style = new Zend_Pdf_Style();
        $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
        foreach ($shipments as $shipment) {
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;

            $order = $shipment->getOrder();

            /* Add image */
            $this->insertLogo($page);

            /* Add address */
            $this->insertAddress($page);

            /* Add head */
            $this->insertOrder($page, $order);

            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
            $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
            $page->drawText(Mage::helper('sales')->__('Packingslip # ') . $shipment->getIncrementId(), 35, 780, 'UTF-8');

            /* Add table */
            $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
            $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);


            /* Add table head */
            $page->drawRectangle(25, $this->y, 570, $this->y-15);
            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
            $page->drawText(Mage::helper('sales')->__('QTY'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Products'), 60, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('SKU'), 470, $this->y, 'UTF-8');

            $this->y -=15;

            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            /* Add body */
            foreach ($shipment->getAllItems() as $item){
                $shift = 10;
                $shift = array();
                if ($this->y<15) {
                    /* Add new table head */
                    $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
                    $pdf->pages[] = $page;
                    $this->y = 800;

                    $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
                    $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
                    $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
                    $page->setLineWidth(0.5);
                    $page->drawRectangle(25, $this->y, 570, $this->y-15);
                    $this->y -=10;

                    $page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
                    $page->drawText(Mage::helper('sales')->__('QTY'), 35, $this->y, 'UTF-8');
                    $page->drawText(Mage::helper('sales')->__('Products'), 60, $this->y, 'UTF-8');
                    $page->drawText(Mage::helper('sales')->__('SKU'), 470, $this->y, 'UTF-8');

                    $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                    $this->y -=20;
                }
                /* Add products */
                $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);

                $page->drawText($item->getQty()*1, 35, $this->y, 'UTF-8');

                if (strlen($item->getName()) > 80) {
                    $drawTextValue = explode(" ", $item->getName());
                    $drawTextParts = array();
                    $i = 0;
                    foreach ($drawTextValue as $drawTextPart) {
                        if (!empty($drawTextParts{$i}) &&
                            (strlen($drawTextParts{$i}) + strlen($drawTextPart)) < 80 ) {
                            $drawTextParts{$i} .= ' '. $drawTextPart;
                        } else {
                            $i++;
                            $drawTextParts{$i} = $drawTextPart;
                        }
                    }
                    $shift{0} = 0;
                    foreach ($drawTextParts as $drawTextPart) {
                        $page->drawText($drawTextPart, 60, $this->y-$shift{0}, 'UTF-8');
                        $shift{0} += 10;
                    }

                } else {
                    $page->drawText($item->getName(), 60, $this->y, 'UTF-8');
                }

                $shift{1} = 10;
                foreach ($this->_parseItemDescription($item) as $description){
                    $page->drawText(strip_tags($description), 65, $this->y-$shift{1}, 'UTF-8');
                    $shift{1} += 10;
                }

                if (strlen($item->getSku()) > 36) {
                    $drawTextValue = str_split($item->getSku(), 36);
                    $shift{2} = 0;
                    foreach ($drawTextValue as $drawTextPart) {
                        $page->drawText($drawTextPart, 440, $this->y-$shift{2}, 'UTF-8');
                        $shift{2} += 10;
                    }

                } else {
                    $page->drawText($item->getSku(), 440, $this->y, 'UTF-8');
                }

                $this->y -=max($shift)+10;
            }
        }
        return $pdf;
    }

}