<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Controller\Webhook\Checkout\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\WebhookFactory;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Response;
use Magento\Sales\Model\OrderFactory;

class WebhookProcessor
{

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Balancepay\Balancepay\Model\WebhookFactory
     */
    protected $webhookFactory;

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
     * Pending
     */
    public const PENDING = 0;

    /**
     * Inprogress
     */
    public const IN_PROGRESS = 1;

    /**
     * Failed
     */
    public const FAILED = 3;

    /**
     * WebhookProcessor constructor.
     *
     * @param OrderFactory $orderFactory
     * @param \Balancepay\Balancepay\Model\WebhookFactory $webhookFactory
     * @param Json $json
     * @param \Balancepay\Balancepay\Model\Config $balancepayConfig
     * @param JsonFactory $jsonResultFactory
     * @param \Balancepay\Balancepay\Model\ChargedProcessor $chargedProcessor
     * @param \Balancepay\Balancepay\Model\ConfirmedProcessor $confirmedProcessor
     */
    public function __construct(
        OrderFactory $orderFactory,
        Config $balancepayConfig,
        JsonFactory $jsonResultFactory,
        ChargedProcessor $chargedProcessor,
        ConfirmedProcessor $confirmedProcessor
    ) {
        $this->orderFactory = $orderFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->chargedProcessor = $chargedProcessor;
        $this->confirmedProcessor = $confirmedProcessor;
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
                $this->addToQueue($params, $webhookName);
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

    /**
     * ProcessWebhookCron
     *
     * @param array $params
     * @param mixed $webhook
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processWebhookCron($params, $webhook)
    {
        $isTransactionSuccess = false;
        $order = $this->orderFactory->create()->loadByIncrementId((string)$params['externalReferenceId']);
        $this->updateWebhookQueue($webhook->getEntityId(), 'status', self::IN_PROGRESS);
        if (!$order || !$order->getId()) {
            if ($webhook->getAttempts() >= 3) {
                $this->updateWebhookQueue($webhook->getEntityId(), 'status', self::FAILED);
            } else {
                $this->updateWebhookQueue($webhook->getEntityId(), 'status', self::PENDING);
                $attempts = $webhook->getAttempts() + 1;
                $this->updateWebhookQueue($webhook->getEntityId(), 'attempts', $attempts);
            }
            throw new LocalizedException(new Phrase("No matching order!"));
        }
        if ($webhook->getName() == Confirmed::WEBHOOK_CONFIRMED_NAME) {
            $isTransactionSuccess = $this->confirmedProcessor->processConfirmedWebhook($params, $order);
        } elseif ($webhook->getName() == Charged::WEBHOOK_CHARGED_NAME) {
            $isTransactionSuccess = $this->chargedProcessor->processChargedWebhook($params, $order);
        }
        if ($isTransactionSuccess) {
            $webhook->delete();
        }
    }

    /**
     * UpdateWebhookQueue
     *
     * @param int $id
     * @param mixed $field
     * @param mixed $value
     * @return bool
     * @throws NoSuchEntityException
     */
    public function updateWebhookQueue($id, $field, $value)
    {
        try {
            $webhookModel = $this->webhookFactory->create()->load($id, 'entity_id');
            $entityId = $webhookModel->getEntityId();
            $webhookModel->setData([
                'entity_id' => $entityId,
                $field => $value
            ])->save();
        } catch (\Exception $e) {
            $this->balancepayConfig->log($e->getMessage());
        }
        return true;
    }
}
