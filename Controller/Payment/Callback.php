<?php

namespace GreenCryptoPay\Merchant\Controller\Payment;

use GreenCryptoPay\Merchant\Model\ConfigManagement;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Request\InvalidRequestException;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ConfigManagement
     */
    private $configManagement;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OrderCommentSender
     */
    private $orderCommentSender;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param RequestInterface $request
     * @param OrderRepository $orderRepository
     * @param ResourceConnection $resourceConnection
     * @param ConfigManagement $configManagement
     * @param JsonFactory $resultJsonFactory
     * @param OrderCommentSender $orderCommentSender
     */
    public function __construct(
        RequestInterface $request,
        OrderRepository $orderRepository,
        ResourceConnection $resourceConnection,
        ConfigManagement $configManagement,
        JsonFactory $resultJsonFactory,
        OrderCommentSender $orderCommentSender,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->connection = $resourceConnection->getConnection();
        $this->configManagement = $configManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderCommentSender = $orderCommentSender;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute action based on request and return result
     *
     */
    public function execute()
    {
        $result = [];

        $data = json_decode(file_get_contents('php://input'), true);
        $order = $this->orderRepository->get($data['order_id']);

        $paymentData = $order->getPayment();

        if ($paymentData['method'] !== $this->configManagement->getName()) {
            throw new Exception('Order #' . $data['order_id'] . ' payment method is not ' . $this->configManagement->getName());
        }

        $sql = 'SELECT * FROM ' . $this->configManagement->getTableName() . ' WHERE order_id=:order_id limit 1';

        $greenCryptoPayData = $this->connection->query($sql, [
            'order_id' => $data['order_id']
        ])->fetch();

        if (empty($greenCryptoPayData)) {
            throw new Exception('Order #' . $data['order_id'] . ' does not exists in orders table');
        }

        if ($data['currency'] !== $greenCryptoPayData['payment_currency']) {
            throw new Exception('Order #' . $data['order_id'] . ' currency does not match');
        }

        if ($data['callback_secret'] !== $greenCryptoPayData['callback_secret']) {
            throw new Exception('Order #' . $data['order_id'] . ' unknown error');
        }

        if ($order->getStatus() == 'pending'
            && $data['amount_received'] >= $greenCryptoPayData['payment_amount']
            && $data['confirmations'] >= $this->configManagement->getNumberOfConfirmations()
        ) {
            $orderState = Order::STATE_PROCESSING;
            $order->setState($orderState)->setStatus($orderState);
            $order->save();
            $this->orderCommentSender->send($order, true);
            $this->notification($order->getIncrementId());
            $result['stop'] = true;
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function notification($orderId)
    {
        $fromEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $fromName = $this->scopeConfig->getValue(
            'trans_email/ident_general/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $toEmail = $fromName = $this->scopeConfig->getValue(
            'trans_email/ident_sales/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $templateVars = [
            'subject' => $this->storeManager->getStore()->getName() . ' - Order paid',
            'order_id' => $orderId,
        ];

        $storeId = $this->storeManager->getStore()->getId();

        $from = ['email' => $fromEmail, 'name' => $fromName];
        $this->inlineTranslation->suspend();

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $templateOptions = [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $storeId
        ];

        $transport = $this->transportBuilder->setTemplateIdentifier('order_paid', $storeScope)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($toEmail)
            ->getTransport();

        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }
}
