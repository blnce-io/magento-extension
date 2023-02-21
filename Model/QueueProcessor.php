<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Controller\Webhook\Transaction\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundCanceled;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundFailed;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundSuccessful;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\OrderFactory;

class QueueProcessor
{
    /**
     * Pending
     */
    public const PENDING = 'pending';

    /**
     * Inprogress
     */
    public const IN_PROGRESS = 'in_progress';

    /**
     * Failed
     */
    public const FAILED = 'failed';

    /**
     * @var \Balancepay\Balancepay\Model\QueueFactory
     */
    private $queueFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ChargedProcessor
     */
    private $chargedProcessor;

    /**
     * @var ConfirmedProcessor
     */
    private $confirmedProcessor;

    /**
     * @var \Balancepay\Balancepay\Model\RefundCanceledProcessor
     */
    private $refundCanceledProcessor;

    /**
     * @var \Balancepay\Balancepay\Model\RefundSuccessfulProcessor
     */
    private $refundSuccessfulProcessor;

    /**
     * @var \Balancepay\Balancepay\Model\RefundFailedProcessor
     */
    private $refundFailedProcessor;

    /**
     * QueueProcessor constructor.
     *
     * @param QueueFactory $queueFactory
     * @param Json $json
     * @param Config $balancepayConfig
     * @param OrderFactory $orderFactory
     * @param ChargedProcessor $chargedProcessor
     * @param ConfirmedProcessor $confirmedProcessor
     * @param \Balancepay\Balancepay\Model\RefundCanceledProcessor $refundCanceledProcessor
     * @param \Balancepay\Balancepay\Model\RefundSuccessfulProcessor $refundSuccessfulProcessor
     * @param \Balancepay\Balancepay\Model\RefundFailedProcessor $refundFailedProcessor
     */
    public function __construct(
        QueueFactory $queueFactory,
        Json $json,
        Config $balancepayConfig,
        OrderFactory $orderFactory,
        ChargedProcessor $chargedProcessor,
        ConfirmedProcessor $confirmedProcessor,
        RefundCanceledProcessor $refundCanceledProcessor,
        RefundSuccessfulProcessor $refundSuccessfulProcessor,
        RefundFailedProcessor $refundFailedProcessor
    ) {
        $this->queueFactory = $queueFactory;
        $this->json = $json;
        $this->balancepayConfig = $balancepayConfig;
        $this->orderFactory = $orderFactory;
        $this->chargedProcessor = $chargedProcessor;
        $this->confirmedProcessor = $confirmedProcessor;
        $this->refundCanceledProcessor = $refundCanceledProcessor;
        $this->refundSuccessfulProcessor = $refundSuccessfulProcessor;
        $this->refundFailedProcessor = $refundFailedProcessor;
    }

    /**
     * AddToQueue
     *
     * @param array $params
     * @param string $name
     * @return void
     * @throws \Exception
     */
    public function addToQueue($params, $name)
    {
        $queueModel = $this->queueFactory->create();
        $queueModel->setData([
            'payload' => $this->json->serialize($params),
            'name' => $name,
            'attempts' => 1
        ]);
        $queueModel->save();
    }

    /**
     * UpdateWebhookQueue
     *
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @return bool
     * @throws NoSuchEntityException
     */
    public function updateWebhookQueue($id, $field, $value)
    {
        try {
            $queueModel = $this->queueFactory->create()->load($id, 'entity_id');
            $entityId = $queueModel->getEntityId();
            $queueModel->setData([
                'entity_id' => $entityId,
                $field => $value
            ])->save();
        } catch (\Exception $e) {
            $this->balancepayConfig->log($e->getMessage());
        }
        return true;
    }

    /**
     * ProcessJob
     *
     * @param string $job
     * @return void
     * @throws NoSuchEntityException
     */
    public function processJob($job)
    {
        try {
            $isJobComplete = false;
            $jobName = $job->getName();
            $jobAttempts = $job->getAttempts();
            $jobEntityId = $job->getEntityId();
            $jobPayload = $job->getPayload();
            $params = (array)$this->json->unserialize($jobPayload);
            if (($jobName == Charged::WEBHOOK_CHARGED_NAME || $jobName == Confirmed::WEBHOOK_CONFIRMED_NAME)) {
                $order = $this->orderFactory->create()->loadByIncrementId((string)$params['externalReferenceId']);
                if ((!$order || !$order->getId())) {
                    throw new LocalizedException(new Phrase("No matching order!"));
                }
            }
            $this->updateWebhookQueue($jobEntityId, 'status', self::IN_PROGRESS);
            if ($jobName == Confirmed::WEBHOOK_CONFIRMED_NAME) {
                $isJobComplete = $this->confirmedProcessor->processConfirmedWebhook($params, $order);
            } elseif ($jobName == Charged::WEBHOOK_CHARGED_NAME) {
                $isJobComplete = $this->chargedProcessor->processChargedWebhook($params, $order);
            } elseif ($jobName == RefundCanceled::WEBHOOK_CANCELED_NAME) {
                $isJobComplete = $this->refundCanceledProcessor->processCanceledWebhook($params, $jobName);
            } elseif ($jobName == RefundSuccessful::WEBHOOK_SUCCESSFUL_NAME) {
                $isJobComplete = $this->refundSuccessfulProcessor->processSuccessfulWebhook($params, $jobName);
            } elseif ($jobName == RefundFailed::WEBHOOK_FAILED_NAME) {
                $isJobComplete = $this->refundFailedProcessor->processFailedWebhook($params, $jobName);
            }
            if ($isJobComplete) {
                $job->delete();
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log($jobName . ' Job Failed, Attempts - ' . $jobAttempts . '
            [Exception: ' . $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
            if ($jobAttempts >= 3) {
                $this->updateWebhookQueue($jobEntityId, 'status', self::FAILED);
            } else {
                $this->updateWebhookQueue($jobEntityId, 'status', self::PENDING);
                $this->updateWebhookQueue($jobEntityId, 'attempts', $jobAttempts + 1);
            }
        }
    }
}
