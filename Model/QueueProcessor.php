<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Controller\Webhook\Checkout\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Model\QueueFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Balancepay\Balancepay\Model\Config;
use Magento\Sales\Model\OrderFactory;

class QueueProcessor
{
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
     * QueueProcessor constructor.
     *
     * @param \Balancepay\Balancepay\Model\QueueFactory $queueFactory
     * @param Json $json
     * @param \Balancepay\Balancepay\Model\Config $balancepayConfig
     * @param OrderFactory $orderFactory
     * @param \Balancepay\Balancepay\Model\ChargedProcessor $chargedProcessor
     * @param \Balancepay\Balancepay\Model\ConfirmedProcessor $confirmedProcessor
     */
    public function __construct(
        QueueFactory $queueFactory,
        Json $json,
        Config $balancepayConfig,
        OrderFactory $orderFactory,
        ChargedProcessor $chargedProcessor,
        ConfirmedProcessor $confirmedProcessor
    ) {
        $this->queueFactory = $queueFactory;
        $this->json = $json;
        $this->balancepayConfig = $balancepayConfig;
        $this->orderFactory = $orderFactory;
        $this->chargedProcessor = $chargedProcessor;
        $this->confirmedProcessor = $confirmedProcessor;
    }

    /**
     * AddToQueue
     *
     * @param array $params
     * @param string $name
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
     * @param $id
     * @param $field
     * @param $value
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
     * ProcessQueueCron
     *
     * @param $params
     * @param $queue
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processQueueCron($params, $queue)
    {
        $isTransactionSuccess = false;
        $order = $this->orderFactory->create()->loadByIncrementId((string)$params['externalReferenceId']);
        $this->updateWebhookQueue($queue->getEntityId(), 'status', self::IN_PROGRESS);
        if (!$order || !$order->getId()) {
            if ($queue->getAttempts() >= 3) {
                $this->updateWebhookQueue($queue->getEntityId(), 'status', self::FAILED);
            } else {
                $this->updateWebhookQueue($queue->getEntityId(), 'status', self::PENDING);
                $attempts = $queue->getAttempts() + 1;
                $this->updateWebhookQueue($queue->getEntityId(), 'attempts', $attempts);
            }
            throw new LocalizedException(new Phrase("No matching order!"));
        }
        if ($queue->getName() == Confirmed::WEBHOOK_CONFIRMED_NAME) {
            $isTransactionSuccess = $this->confirmedProcessor->processConfirmedWebhook($params, $order);
        } elseif ($queue->getName() == Charged::WEBHOOK_CHARGED_NAME) {
            $isTransactionSuccess = $this->chargedProcessor->processChargedWebhook($params, $order);
        }
        if ($isTransactionSuccess) {
            $queue->delete();
        }
    }
}
