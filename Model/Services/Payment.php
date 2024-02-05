<?php

declare(strict_types = 1);

namespace GreenCryptoPay\Merchant\Model\Services;

use GreenCryptoPay\Merchant\Api\PaymentInterface;
use GreenCryptoPay\Merchant\Api\Response\PlaceOrderInterface as Response;
use GreenCryptoPay\Merchant\Model\Payment as GreenCryptoPayPayment;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

/**
 * Class Payment
 */
class Payment implements PaymentInterface
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var GreenCryptoPayPayment
     */
    private $greenCryptoPayPayment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Response $response
     * @param CheckoutSession $checkoutSession
     * @param OrderRepository $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param GreenCryptoPayPayment $greenCryptoPayPayment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Response $response,
        CheckoutSession $checkoutSession,
        OrderRepository $orderRepository,
        CartRepositoryInterface $quoteRepository,
        GreenCryptoPayPayment $greenCryptoPayPayment,
        LoggerInterface $logger
    ) {
        $this->response = $response;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->greenCryptoPayPayment = $greenCryptoPayPayment;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function placeOrder(): Response
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $code = $store->getStore()->getCurrentCurrencyCode();

        if(!in_array(strtolower($code), \GreenCryptoPay\Merchant\Model\Payment::FROM_CURRENCIES)) {
            throw new \Exception('Bad Request', 400);
        }

        $orderId = $this->checkoutSession->getLastOrderId();

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (InputException | NoSuchEntityException $exception) {
            $this->logger->critical($exception->getMessage());
            $this->response->setStatus(false);
            return $this->response;
        }

        if (!$order->getIncrementId()) {
            $this->response->setStatus(false);
            return $this->response;
        }

        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1);
        $this->quoteRepository->save($quote);
        $payment_url = $this->greenCryptoPayPayment->getgreencryptopayOrder($order);

        if (!empty($payment_url)) {
            $this->response->setStatus(true);
            $this->response->setPaymentUrl($payment_url);
            return $this->response;
        }

        $this->response->setStatus(false);
        return $this->response;
    }
}
