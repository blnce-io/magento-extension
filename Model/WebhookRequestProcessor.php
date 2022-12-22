<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Controller\Webhook\Checkout\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundCanceled;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundFailed;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundSuccessful;
use Laminas\Crypt\Hmac;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Response;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Model\QueueProcessor;

class WebhookRequestProcessor
{

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Config
     */
    protected $balancepayConfig;

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \Balancepay\Balancepay\Model\ChargedProcessor
     */
    protected $chargedProcessor;

    /**
     * @var \Balancepay\Balancepay\Model\ConfirmedProcessor
     */
    protected $confirmedProcessor;
    /**
     * @var Hmac
     */
    protected $hmac;
    /**
     * @var \Balancepay\Balancepay\Model\QueueProcessor
     */
    private $queueProcessor;

    /**
     * WebhookRequestProcessor constructor.
     *
     * @param OrderFactory $orderFactory
     * @param Config $balancepayConfig
     * @param JsonFactory $jsonResultFactory
     * @param ChargedProcessor $chargedProcessor
     * @param ConfirmedProcessor $confirmedProcessor
     * @param \Balancepay\Balancepay\Model\QueueProcessor $queueProcessor
     * @param Json $json
     * @param Hmac $hmac
     */
    public function __construct(
        OrderFactory $orderFactory,
        Config $balancepayConfig,
        JsonFactory $jsonResultFactory,
        ChargedProcessor $chargedProcessor,
        ConfirmedProcessor $confirmedProcessor,
        QueueProcessor $queueProcessor,
        Json $json,
        Hmac $hmac
    ) {
        $this->orderFactory = $orderFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->chargedProcessor = $chargedProcessor;
        $this->confirmedProcessor = $confirmedProcessor;
        $this->queueProcessor = $queueProcessor;
        $this->json = $json;
        $this->hmac = $hmac;
    }

    /**
     * ProcessWebhook
     *
     * @param mixed $content
     * @param mixed $headers
     * @param string $webhookName
     * @return \Magento\Framework\Controller\Result\Json
     * @throws NoSuchEntityException
     */
    public function process($content, $headers, $webhookName)
    {
        try {
            $params = $this->validateSignature($content, $headers, $webhookName);
            $this->queueProcessor->addToQueue($params, $webhookName);
            return $this->jsonResultFactory->create()->setHttpResponseCode(Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Webhook
            [Exception: ' . $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
            return $this->jsonResultFactory->create()->setHttpResponseCode(Response::STATUS_CODE_400);
        }
    }

    /**
     * ValidateSignature
     *
     * @param mixed $content
     * @param mixed $headers
     * @param string $webhookName
     * @return array
     * @throws LocalizedException
     */
    public function validateSignature($content, $headers, $webhookName): array
    {
        $signature = Hmac::compute($this->balancepayConfig->getWebhookSecret(), "sha256", $content);
        if ($signature !== $headers['X-Blnce-Signature']) {
            throw new LocalizedException(new Phrase("Signature is doesn't match!"));
        }
        $params = (array)$this->json->unserialize($content);

        if ($webhookName == Confirmed::WEBHOOK_CONFIRMED_NAME) {
            $requiredKeys = ['externalReferenceId', 'isFinanced', 'selectedPaymentMethod'];
        } elseif ($webhookName == Charged::WEBHOOK_CHARGED_NAME) {
            $requiredKeys = ['externalReferenceId', 'chargeId', 'amount'];
        } elseif ($webhookName == RefundSuccessful::WEBHOOK_SUCCESSFUL_NAME
            || $webhookName == RefundCanceled::WEBHOOK_CANCELED_NAME
            || $webhookName == RefundFailed::WEBHOOK_FAILED_NAME
        ) {
            $requiredKeys = ['externalReferenceId', 'selectedPaymentMethod', 'status'];
        }

        $bodyKeys = array_keys($params);
        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new LocalizedException(
                new Phrase(
                    'Balance webhook required fields are missing: %1.',
                    [implode(', ', $diff)]
                )
            );
        }
        return $params;
    }
}
