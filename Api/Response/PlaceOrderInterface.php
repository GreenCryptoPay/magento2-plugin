<?php

declare(strict_types = 1);

namespace GreenCryptoPay\Merchant\Api\Response;

/**
 * Interface PlaceOrderInterface
 */
interface PlaceOrderInterface
{
    /**
     * @return string
     */
    public function getPaymentUrl(): string;

    /**
     * @param string $paymentUrl
     *
     * @return void
     */
    public function setPaymentUrl(string $paymentUrl): void;

    /**
     * @return bool
     */
    public function getStatus(): bool;

    /**
     * @param bool $status
     *
     * @return void
     */
    public function setStatus(bool $status): void;
}
