<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Controller\Webhook\Checkout\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Response;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Model\QueueProcessor;

class WebhookProcessor
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
     * @var \Balancepay\Balancepay\Model\QueueProcessor
     */
    private $queueProcessor;

    /**
     * WebhookProcessor constructor.
     *
     * @param OrderFactory $orderFactory
     * @param \Balancepay\Balancepay\Model\Config $balancepayConfig
     * @param JsonFactory $jsonResultFactory
     * @param \Balancepay\Balancepay\Model\ChargedProcessor $chargedProcessor
     * @param \Balancepay\Balancepay\Model\ConfirmedProcessor $confirmedProcessor
     * @param \Balancepay\Balancepay\Model\QueueProcessor $queueProcessor
     */
    public function __construct(
        OrderFactory $orderFactory,
        Config $balancepayConfig,
        JsonFactory $jsonResultFactory,
        ChargedProcessor $chargedProcessor,
        ConfirmedProcessor $confirmedProcessor,
        QueueProcessor $queueProcessor
    ) {
        $this->orderFactory = $orderFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->chargedProcessor = $chargedProcessor;
        $this->confirmedProcessor = $confirmedProcessor;
        $this->queueProcessor = $queueProcessor;
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
    public function processWebhook($content, $headers, $webhookName)
    {
        $resBody = [];
        try {
            $params = $this->validateSignature($content, $headers, $webhookName);
            $externalReferenceId = (string)$params['externalReferenceId'];

            $order = $this->orderFactory->create()->loadByIncrementId($externalReferenceId);

            if (!$order || !$order->getId()) {
                $this->queueProcessor->addToQueue($params, $webhookName);
                throw new LocalizedException(new Phrase("No matching order!"));
            }

            if ($webhookName == Confirmed::WEBHOOK_CONFIRMED_NAME) {
                $this->confirmedProcessor->processConfirmedWebhook($params, $order);
            } elseif ($webhookName == Charged::WEBHOOK_CHARGED_NAME) {
                $this->chargedProcessor->processChargedWebhook($params, $order);
            }

            $resBody = [
                "error" => 0,
                "message" => "Success",
                "order" => $order->getIncrementId()
            ];

        } catch (\Exception $e) {
            $this->balancepayConfig->log('Webhook
            [Exception: ' . $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
            $resBody = [
                "error" => 1,
                "message" => $e->getMessage(),
            ];
            if ($this->balancepayConfig->isDebugEnabled()) {
                $resBody["trace"] = $e->getTraceAsString();
            }
        }
        return $this->jsonResultFactory->create()
            ->setHttpResponseCode(Response::HTTP_OK)
            ->setData($resBody);
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
        $signature = hash_hmac("sha256", $content, $this->balancepayConfig->getWebhookSecret());
        if ($signature !== $headers['X-Blnce-Signature']) {
            throw new LocalizedException(new Phrase("Signature is doesn't match!"));
        }
        $params = (array)$this->json->unserialize($content);

        if ($webhookName == Confirmed::WEBHOOK_CONFIRMED_NAME) {
            $requiredKeys = ['externalReferenceId', 'isFinanced', 'selectedPaymentMethod'];
        } elseif ($webhookName == Charged::WEBHOOK_CHARGED_NAME) {
            $requiredKeys = ['externalReferenceId', 'chargeId', 'amount'];
        }
        $bodyKeys = array_keys($params);
        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new LocalizedException(
                new Phrase(
                    'Balancepay webhook required fields are missing: %1.',
                    [implode(', ', $diff)]
                )
            );
        }
        return $params;
    }
}
