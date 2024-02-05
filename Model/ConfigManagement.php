<?php

declare(strict_types = 1);

namespace GreenCryptoPay\Merchant\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Module\ResourceInterface;
use Exception;

/**
 * Class ConfigManagement
 */
class ConfigManagement
{
    private const NAME = 'greencryptopay_merchant';
    private const TABLE_NAME = 'greencryptopay_orders';
    private const TESTNET = 'payment/greencryptopay_merchant/testnet';
    private const MERCHANT_ID = 'payment/greencryptopay_merchant/merchant_id';
    private const SECRET_KEY = 'payment/greencryptopay_merchant/secret_key';
    private const NUMBER_OF_CONFIRMATIONS = 'payment/greencryptopay_merchant/number_of_confirmations';
    private const REQUEST_SIGNATURE = 'payment/greencryptopay_merchant/request_signature';
    private const TITLE = 'payment/greencryptopay_merchant/title';
    private const WALLET_LINK = 'payment/greencryptopay_merchant/wallet_link';
    private const TIME_TO_PAY = 'payment/greencryptopay_merchant/time_to_pay';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @var null
     */
    private $storeId = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param ResourceInterface $resource
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ResourceInterface $resource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->resource->getDataVersion(self::NAME);
    }

    /**
     * @return bool
     */
    public function getTestnet(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::TESTNET,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMerchantId(): string
    {
        $merchantId = $this->scopeConfig->getValue(
            self::MERCHANT_ID,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );

        if (empty($merchantId)) {
         throw new Exception('The "Merchant id" parameter must be filled in the plugin settings.');
        }

        return $merchantId;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getSecretKey(): string
    {
        $secretKey = $this->scopeConfig->getValue(
            self::SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );

        if (empty($secretKey)) {
            throw new Exception('The "Secret Key" parameter must be filled in the plugin settings.');
        }

        return $secretKey;
    }

    /**
     * @return int|mixed
     */
    public function getNumberOfConfirmations(): ?int
    {
        $numberOfConfirmations = $this->scopeConfig->getValue(
            self::NUMBER_OF_CONFIRMATIONS,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );

        return (int) $numberOfConfirmations ?? 3;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getRequestSignature(): string
    {
        $requestSignature = $this->scopeConfig->getValue(
            self::REQUEST_SIGNATURE,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );

        if (empty($requestSignature)) {
            throw new Exception('The "Request signature" parameter must be filled in the plugin settings.');
        }

        return $requestSignature;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->scopeConfig->getValue(
            self::TITLE,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }

    /**
     * @return mixed
     */
    public function getWalletLink()
    {
        return $this->scopeConfig->getValue(
            self::WALLET_LINK,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }

    /**
     * @return mixed
     */
    public function getTimeToPay()
    {
        return $this->scopeConfig->getValue(
            self::TIME_TO_PAY,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }

    /**
     * Get Store Id
     *
     * @return int|null
     */
    private function getStoreId(): ?int
    {
        if (!$this->storeId) {
            try {
                $store = $this->storeManager->getStore();
                $this->storeId = (int) $store->getId();
            } catch (NoSuchEntityException $exception) {
                $this->logger->critical($exception->getMessage());

                return $this->storeId;
            }
        }

        return $this->storeId;
    }
}
