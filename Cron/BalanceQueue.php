<?php

namespace Balancepay\Balancepay\Cron;

use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\QueueFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Balancepay\Balancepay\Model\QueueProcessor;

class BalanceQueue
{
    /**
     * @var Json
     */
    private $json;

    /**
     * @var QueueProcessor
     */
    private $queueProcessor;

    /**
     * @var Config
     */
    protected $balancepayConfig;

    /**
     * @var QueueFactory
     */
    private $queueFactory;

    /**
     * Queue constructor.
     *
     * @param QueueProcessor $queueProcessor
     * @param QueueFactory $queueFactory
     * @param Json $json
     * @param Config $balancepayConfig
     */
    public function __construct(
        QueueProcessor $queueProcessor,
        QueueFactory $queueFactory,
        Json $json,
        Config $balancepayConfig
    ) {
        $this->queueProcessor = $queueProcessor;
        $this->queueFactory = $queueFactory;
        $this->json = $json;
        $this->balancepayConfig = $balancepayConfig;
    }

    /**
     * Execute
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        $queueCollection = $this->queueFactory->create()->getCollection();
        $queueCollection->addFieldToFilter('status', ['eq' => QueueProcessor::PENDING]);
        foreach ($queueCollection as $queue) {
            $params = (array)$this->json->unserialize($queue->getPayload());
            try {
                $this->queueProcessor->processQueueCron($params, $queue);
            } catch (LocalizedException $e) {
                $this->balancepayConfig->log($e->getMessage());
            }
        }
    }
}
