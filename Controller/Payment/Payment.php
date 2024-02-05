<?php

declare(strict_types=1);

namespace GreenCryptoPay\Merchant\Controller\Payment;

use GreenCryptoPay\Merchant\Model\ConfigManagement;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\View\Asset\Repository;

function dd($data)
{
    echo '<pre>';
    if (is_object($data) && method_exists($data, 'debug')) {
        print_r($data->debug());
    } else {
        print_r($data);
    }
    die;
}

class Payment extends Action implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ConfigManagement
     */
    private $configManagement;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ConfigManagement $configManagement
     * @param ResultFactory $resultFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ConfigManagement $configManagement,
        ResultFactory $resultFactory,
        UrlInterface $urlBuilder
    )
    {
        parent::__construct($context);
        $this->request = $request;
        $this->configManagement = $configManagement;
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $inputSignature = $params['signature'];

        $orderId = $params['order_id'];

        unset($params['signature']);
        $signature = sha1(http_build_query($params) . $this->configManagement->getRequestSignature());

        if ($inputSignature != $signature) {
            throw new Exception('Bad Request', 400);
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $order = $objectManager->create(OrderRepository::class)->get($orderId);
        $connection = $objectManager->get(ResourceConnection::class)->getConnection();
        $assetRepository = $objectManager->get(Repository::class);

        $sql = 'SELECT * FROM ' . $this->configManagement->getTableName() . ' WHERE order_id=:order_id limit 1';

        $greenCryptoPayData = $connection->query($sql, [
            'order_id' => $orderId
        ])->fetch();

        $layoutFactory = $this->_objectManager->get(LayoutFactory::class);

        $jquery = 'GreenCryptoPay_Merchant::js/view/payment/jquery.min.js';
        $params = ['area' => 'frontend'];
        $jquery = $assetRepository->createAsset($jquery, $params);
        
        $assetPath = str_replace('jquery.min.js', '', $jquery->getUrl());

        $output = $layoutFactory->create()
            ->createBlock(Template::class)
            ->setOrderId()
            ->setPaymentAddress($greenCryptoPayData['payment_address'])
            ->setTotal($order->getGrandTotal())
            ->setAmount($greenCryptoPayData['payment_amount'])
            ->setPaymentMethod($greenCryptoPayData['payment_currency'])
            ->setCurrency($order->getOrderCurrencyCode())
            ->setAssetPath($assetPath)
            ->setWalletLink($this->configManagement->getWalletLink())
            ->setTimeToPay($this->configManagement->getTimeToPay())
            ->setTemplate('GreenCryptoPay_Merchant::payment.phtml')
            ->toHtml();

        $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        $this->_objectManager->create(Cart::class)->truncate()->saveQuote();

        return $rawResult->setContents($output);
    }
}
