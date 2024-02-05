<?php

declare(strict_types = 1);

namespace GreenCryptoPay\Merchant\Model\Response;

use GreenCryptoPay\Merchant\Api\Response\PlaceOrderInterface as Response;

/**
 * Class PlaceOrder
 */
class PlaceOrder implements Response
{
    /**
     * @var string
     */
    private $paymentUrl = '';

    /**
     * @var bool
     */
    private $status = false;

    /**
     * @inheritDoc
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * @inheritDoc
     */
    public function setPaymentUrl(string $paymentUrl): void
    {
        $this->paymentUrl = $paymentUrl;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }
}
