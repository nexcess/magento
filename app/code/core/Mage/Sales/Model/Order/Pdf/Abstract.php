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
abstract class Mage_Sales_Model_Order_Pdf_Abstract extends Varien_Object
{
    protected $y;

    abstract public function getPdf();

    /**
* Returns the total width in points of the string using the specified font and
* size.
*
* This is not the most efficient way to perform this calculation. I'm
* concentrating optimization efforts on the upcoming layout manager class.
* Similar calculations exist inside the layout manager class, but widths are
* generally calculated only after determining line fragments.
*
* @param string $string
* @param Zend_Pdf_Resource_Font $font
* @param float $fontSize Font size in points
* @return float
*/
    protected function widthForStringUsingFontSize($string, $font, $fontSize)
    {
        $drawingString = iconv('UTF-8', 'UTF-16BE//IGNORE', $string);
        $characters = array();
        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;
        return $stringWidth;

    }

    protected function insertLogo(&$page)
    {
        $image = Mage::getStoreConfig('sales/identity/logo');
        if ($image) {
            $image = Mage::getStoreConfig('system/filesystem/media') . '/sales/store/logo/' . $image;
            if (is_file($image)) {

                $image = Zend_Pdf_Image::imageWithPath($image);
                $page->drawImage($image, 25, 800, 125, 825);
            }
        }
        //return $page;
    }

    protected function insertAddress(&$page)
    {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 5);

        $page->setLineWidth(0.5);
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->drawLine(125, 825, 125, 790);

        $page->setLineWidth(0);
        $this->y = 820;
        foreach (explode("\n", Mage::getStoreConfig('sales/identity/address')) as $value){
            if ($value!=='') {
                $page->drawText(trim(strip_tags($value)), 130, $this->y, 'UTF-8');
                $this->y -=7;
            }
        }
        //return $page;
    }

    protected function insertOrder(&$page, $order)
    {

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.5));

        $page->drawRectangle(25, 790, 570, 755);

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);


        $page->drawText(Mage::helper('sales')->__('Order # ').$order->getRealOrderId(), 35, 770, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Order Date: ') . date( 'D M j Y', strtotime( $order->getCreatedAt() ) ), 35, 760, 'UTF-8');

        $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, 755, 275, 730);
        $page->drawRectangle(275, 755, 570, 730);

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 7);
        $page->drawText(Mage::helper('sales')->__('SOLD TO:'), 35, 740 , 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('SHIP TO:'), 285, 740 , 'UTF-8');

        $billingAddress  = explode('|', $order->getBillingAddress()->format('pdf'));
        $shippingAddress = explode('|', $order->getShippingAddress()->format('pdf'));

        $y = 730-max(count($billingAddress), count($shippingAddress))*10+5;

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, 730, 570, $y);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);

        $this->y = 720;

        foreach ($billingAddress as $value){
            if ($value!=='') {
                $page->drawText(strip_tags($value), 35, $this->y, 'UTF-8');
                $this->y -=10;
            }
        }

        $this->y = 720;
        foreach ($shippingAddress as $value){
            if ($value!=='') {
                $page->drawText(strip_tags($value), 285, $this->y, 'UTF-8');
                $this->y -=10;
            }

        }

        $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 275, $this->y-25);
        $page->drawRectangle(275, $this->y, 570, $this->y-25);

        $this->y -=15;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 7);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y , 'UTF-8');

        $this->y -=10;
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $payment = explode('{{pdf_row_separator}}', Mage::helper('payment')->getInfoBlock($order->getPayment())->toPdf());
        foreach ($payment as $key=>$value){
            if (strip_tags(trim($value))==''){
                unset($payment[$key]);
            }
        }
        reset($payment);

        $page->drawRectangle(25, $this->y, 570, $this->y-count($payment)*10-15);

        $this->y -=15;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

        $page->drawText($order->getShippingDescription(), 285, $this->y, 'UTF-8');
        foreach ($payment as $value){
            if (trim($value)!=='') {
                $page->drawText(strip_tags(trim($value)), 35, $this->y, 'UTF-8');
                $this->y -=10;
            }
        }

        $this->y -= 15;
    }

    protected function insertTotals(&$page, $source){
        $order = $source->getOrder();
        $font  = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);

        $page->setFont($font, 7);

        $order_subtotal = Mage::helper('sales')->__('Order Subtotal:');
        $page->drawText($order_subtotal, 475-$this->widthForStringUsingFontSize($order_subtotal, $font, 7), $this->y, 'UTF-8');

        $order_subtotal = $order->formatPriceTxt($source->getSubtotal());
        $page->drawText($order_subtotal, 565-$this->widthForStringUsingFontSize($order_subtotal, $font, 7), $this->y, 'UTF-8');
        $this->y -=15;

        if ((float)$source->getDiscountAmount()){
            $discount = Mage::helper('sales')->__('Discount :');
            $page->drawText($discount, 475-$this->widthForStringUsingFontSize($discount, $font, 7), $this->y, 'UTF-8');

            $discount = $order->formatPriceTxt(0.00 - $source->getDiscountAmount());
            $page->drawText($discount, 565-$this->widthForStringUsingFontSize($discount, $font, 7), $this->y, 'UTF-8');
            $this->y -=15;
        }

        if ((float)$source->getShippingAmount()){
            $order_shipping = Mage::helper('sales')->__('Shipping & Handling:');
            $page->drawText($order_shipping, 475-$this->widthForStringUsingFontSize($order_shipping, $font, 7), $this->y, 'UTF-8');

            $order_shipping = $order->formatPriceTxt($source->getShippingAmount());
            $page->drawText($order_shipping, 565-$this->widthForStringUsingFontSize($order_shipping, $font, 7), $this->y, 'UTF-8');
            $this->y -=15;
        }

        if ($source->getAdjustmentPositive()){
            $adjustment_refund = Mage::helper('sales')->__('Adjustment Refund:');
            $page ->drawText($adjustment_refund, 475-$this->widthForStringUsingFontSize($adjustment_refund, $font, 7), $this->y, 'UTF-8');

            $adjustment_refund = $order->formatPriceTxt($source->getAdjustmentPositive());
            $page ->drawText($adjustment_refund, 565-$this->widthForStringUsingFontSize($adjustment_refund, $font, 7), $this->y, 'UTF-8');
            $this->y -=15;
        }

        if ((float) $source->getAdjustmentNegative()){
            $adjustment_fee = Mage::helper('sales')->__('Adjustment Fee:');
            $page ->drawText($adjustment_fee, 475-$this->widthForStringUsingFontSize($adjustment_fee, $font, 7), $this->y, 'UTF-8');

            $adjustment_fee=$order->formatPriceTxt($source->getAdjustmentNegative());
            $page ->drawText($adjustment_fee, 565-$this->widthForStringUsingFontSize($adjustment_fee, $font, 7), $this->y, 'UTF-8');
            $this->y -=15;
        }

        $page->setFont($font, 8);

        $order_grandtotal = Mage::helper('sales')->__('Grand Total:');
        $page ->drawText($order_grandtotal, 475-$this->widthForStringUsingFontSize($order_grandtotal, $font, 8), $this->y, 'UTF-8');

        $order_grandtotal = $order->formatPriceTxt($source->getGrandTotal());
        $page ->drawText($order_grandtotal, 565-$this->widthForStringUsingFontSize($order_grandtotal, $font, 8), $this->y, 'UTF-8');
        $this->y -=15;
    }

    protected function _parseItemDescription($item)
    {
        $description = $item->getDescription();
        if (preg_match_all('/<li.*?>(.*?)<\/li>/i', $description, $matches)) {
            return $matches[1];
        }

        return array($description);
    }
}