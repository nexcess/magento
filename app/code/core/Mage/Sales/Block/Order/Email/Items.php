<?php

class Mage_Sales_Block_Order_Email_Items extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('email/order/items.phtml');
    }
}