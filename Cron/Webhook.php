<?php

namespace Balancepay\Balancepay\Cron;

use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\WebhookFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Balancepay\Balancepay\Model\WebhookProcessor;

class Webhook
{

    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var WebhookProcessor
     */
    private $webhookProcessor;

    /**
     * @var Config
     */
    protected $balancepayConfig;

    /**
     * @param WebhookProcessor $webhookProcessor
     * @param WebhookFactory $webhookFactory
     * @param Json $json
     * @param Config $balancepayConfig
     */
    public function __construct(
        WebhookProcessor $webhookProcessor,
        WebhookFactory $webhookFactory,
        Json $json,
        Config $balancepayConfig
    ) {
        $this->webhookProcessor = $webhookProcessor;
        $this->webhookFactory = $webhookFactory;
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
        $webhookCollection = $this->webhookFactory->create()->getCollection();
        $webhookCollection->addFieldToFilter('status', ['eq' => WebhookProcessor::PENDING]);
        foreach ($webhookCollection as $webhook) {
            $params = (array)$this->json->unserialize($webhook->getPayload());
            try {
                $this->webhookProcessor->processWebhookCron($params, $webhook);
            } catch (LocalizedException $e) {
                $this->balancepayConfig->log($e->getMessage());
            }
        }
    }
}
