<?php

namespace GreenCryptoPay\Merchant\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use GreenCryptoPay\Merchant\Model\Payment;

class PaymentMethodAvailable implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $code = $store->getStore()->getCurrentCurrencyCode();

        if(!in_array(strtolower($code), Payment::FROM_CURRENCIES)) {
            $checkResult = $observer->getEvent()->getResult();
            $checkResult->setData('is_available', false);
        }
    }
}