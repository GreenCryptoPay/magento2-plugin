<?php

declare(strict_types = 1);

namespace GreenCryptoPay\Merchant\Api;

use GreenCryptoPay\Merchant\Api\Response\PlaceOrderInterface;

/**
 * Interface PaymentInterface
 */
interface PaymentInterface
{
    /**
     * @return \GreenCryptoPay\Merchant\Api\Response\PlaceOrderInterface
     */
    public function placeOrder(): PlaceOrderInterface;
}
