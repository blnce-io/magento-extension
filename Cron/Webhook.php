<?php

namespace Balancepay\Balancepay\Cron;

use Balancepay\Balancepay\Model\WebhookFactory;
use Magento\Framework\Exception\LocalizedException;
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
     * Webhook constructor.
     *
     * @param Data $helperData
     * @param WebhookFactory $webhookFactory
     * @param Json $json
     */
    public function __construct(WebhookProcessor $webhookProcessor, WebhookFactory $webhookFactory, Json $json)
    {
        $this->webhookProcessor = $webhookProcessor;
        $this->webhookFactory = $webhookFactory;
        $this->json = $json;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $webhookCollection = $this->webhookFactory->create()->getCollection();
        foreach ($webhookCollection as $webhook) {
            $params = (array)$this->json->unserialize($webhook->getPayload());
            try {
                $this->webhookProcessor->processWebhookCron($params, $webhook);
            } catch (LocalizedException $e) {
            }
        }
    }

}
