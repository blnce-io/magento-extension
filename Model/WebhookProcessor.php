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
        WebhookFactory $webhookFactory,
        Json $json,
        Config $balancepayConfig,
        JsonFactory $jsonResultFactory,
        ChargedProcessor $chargedProcessor,
        ConfirmedProcessor $confirmedProcessor
    )
    {
        $this->orderFactory = $orderFactory;
        $this->webhookFactory = $webhookFactory;
        $this->json = $json;
        $this->balancepayConfig = $balancepayConfig;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->chargedProcessor = $chargedProcessor;
        $this->confirmedProcessor = $confirmedProcessor;
    }

    /**
     * ProcessWebhook
     *
     * @param $content
     * @param $headers
     * @param $webhookName
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processWebhook($content, $headers, $webhookName)
    {
        $resBody = [];
        try {
            $params = $this->validateSignature($content, $headers, $webhookName);
            $externalReferenceId = (string)$params['externalReferenceId'];

            $order = $this->orderFactory->create()->loadByIncrementId($externalReferenceId);

            if (!$order || !$order->getId()) {
                $this->enqueueWebhook($params, $webhookName);
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
     * @param $content
     * @param $headers
     * @param $webhookName
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
     * EnqueueWebhook
     *
     * @param $params
     * @param $name
     * @throws \Exception
     */
    public function enqueueWebhook($params, $name)
    {
        $webhookModel = $this->webhookFactory->create();
        $webhookModel->setData([
            'payload' => $this->json->serialize($params),
            'name' => $name,
            'attempts' => 1
        ]);
        $webhookModel->save();
    }

    /**
     * @param $params
     * @param $webhook
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processWebhookCron($params, $webhook)
    {
        $isTransactionSuccess = false;
        $order = $this->orderFactory->create()->loadByIncrementId((string)$params['externalReferenceId']);
        if (!$order || !$order->getId()) {
            $attempts = $webhook->getAttempts() + 1;
            $this->updateAttempts($webhook->getEntityId(), 'attempts', $attempts);
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
     * UpdateAttempts
     *
     * @param $id
     * @param $field
     * @param $value
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateAttempts($id, $field, $value): bool
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
