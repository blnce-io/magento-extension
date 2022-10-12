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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Balancepay\Balancepay\Model\WebhookRequestProcessor;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Helper\Data;

/**
 * Balancepay transaction/refundcanceled webhook.
 */
class RefundCanceled extends Action implements CsrfAwareActionInterface
{
    public const WEBHOOK_CANCELED_NAME = 'transaction/refund_canceled';

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
     * @var WebhookRequestProcessor
     */
    private $webhookRequestProcessor;

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
     * @param WebhookRequestProcessor $webhookRequestProcessor
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        Json $json,
        OrderFactory $orderFactory,
        Data $helperData,
        WebhookRequestProcessor $webhookRequestProcessor
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->json = $json;
        $this->orderFactory = $orderFactory;
        $this->helperData = $helperData;
        $this->webhookRequestProcessor = $webhookRequestProcessor;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->balancepayConfig->isActive()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $content = $this->getRequest()->getContent();
        $headers = $this->getRequest()->getHeaders()->toArray();
        $this->balancepayConfig->log('Webhook\Transaction\RefundCanceled::execute() ', 'debug', [
            'content' => $content,
            'headers' => $headers,
        ]);
        return $this->webhookRequestProcessor->process($content, $headers, self::WEBHOOK_CANCELED_NAME);
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
