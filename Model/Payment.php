<?php

declare(strict_types = 1);

namespace GreenCryptoPay\Merchant\Model;

use Exception;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use GcpSdk\Api;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface ;

/**
 * Class Payment
 */
class Payment
{
    const TO_CURRENCIES = [
        'btc'
    ];

    const FROM_CURRENCIES = [
        'usd'
    ];

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ConfigManagement
     */
    private $configManagement;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @param UrlInterface $urlBuilder
     * @param ConfigManagement $configManagement
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ConfigManagement $configManagement,
        ResourceConnection $resourceConnection
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->configManagement = $configManagement;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @param OrderInterface $order
     * @throws Exception
     */
    public function getGreenCryptoPayOrder(OrderInterface $order)
    {
        $client = $this->getClient();

        $orderId = $order->getEntityId();
        $total = $order->getGrandTotal();

        $to_currency = self::TO_CURRENCIES[0];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $code = $store->getStore()->getCurrentCurrencyCode();

        $from_currency = strtolower($code);

        $callbackUrl = 'https://gcp.keytabledba.xyz'; //$this->urlBuilder->getUrl('greencryptopay/payment/callback');

        $response = $client->paymentAddress(
            $to_currency,
            $callbackUrl,
            (string) $orderId,
            $from_currency,
            (float) $total
        );

        $sql = "INSERT INTO " . $this->configManagement->getTableName() . " (`order_id`, `callback_secret`, `payment_currency`, `payment_amount`, `payment_address`)
                VALUES (:order_id, :callback_secret, :payment_currency, :payment_amount, :payment_address)";

        $this->connection->query($sql, [
            'order_id' => $orderId,
            'callback_secret' => $response['callback_secret'],
            'payment_currency' => $to_currency,
            'payment_amount' => $response['amount'],
            'payment_address' => $response['payment_address'],
        ]);

        $query_string = [
            'order_id' => $orderId
        ];

        $query_string['signature'] = sha1(http_build_query($query_string) . $this->configManagement->getRequestSignature());

        return $this->urlBuilder->getUrl('greencryptopay/payment/payment') . '?' . http_build_query($query_string);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function getClient()
    {
        $merchantId = $this->configManagement->getMerchantId();
        $secretKey = $this->configManagement->getSecretKey();
        $testnet = $this->configManagement->getTestnet();

        $client = Api::make('standard', $testnet);

        $client->setMerchantId($merchantId);
        $client->setSecretKey($secretKey);

        return $client;
    }
}
