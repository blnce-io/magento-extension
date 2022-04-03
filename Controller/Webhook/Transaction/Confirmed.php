<?php
/**
 * Balance Payments For Magento 2
 * https://www.getbalance.com/
 *
 * @category Balance
 * @package  Balancepay_Balancepay
 * @author   Developer: Pniel Cohen
 * @author   Company: Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Balancepay\Balancepay\Controller\Webhook\Transaction;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Balancepay\Balancepay\Model\WebhookProcessor;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Helper\Data;

/**
 * Balancepay transaction/confirmed webhook.
 */
class Confirmed extends Action implements CsrfAwareActionInterface
{
    public const WEBHOOK_CONFIRMED_NAME = 'transaction/confirmed';

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var BalancepayConfig
     */
    private $balancepayConfig;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var WebhookProcessor
     */
    private $webhookProcessor;

    /**
     * Confirmed constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param BalancepayConfig $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param Json $json
     * @param OrderFactory $orderFactory
     * @param Data $helperData
     * @param WebhookProcessor $webhookProcessor
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        Json $json,
        OrderFactory $orderFactory,
        Data $helperData,
        WebhookProcessor $webhookProcessor
    )
    {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->json = $json;
        $this->orderFactory = $orderFactory;
        $this->helperData = $helperData;
        $this->webhookProcessor = $webhookProcessor;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->balancepayConfig->isActive()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }
        $content = $this->getRequest()->getContent();
        $headers = $this->getRequest()->getHeaders()->toArray();

        $this->balancepayConfig->log('Webhook\Checkout\Confirmed::execute() ', 'debug', [
            'content' => $content,
            'headers' => $headers,
        ]);
        $this->webhookProcessor->processWebhook($content, $headers, self::WEBHOOK_CONFIRMED_NAME);
    }

    /**
     * CreateCsrfValidationException
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * ValidateForCsrf
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
