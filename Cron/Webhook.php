<?php

namespace Balancepay\Balancepay\Cron;

use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\WebhookFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

class Webhook
{

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * Webhook constructor.
     *
     * @param Data $helperData
     * @param WebhookFactory $webhookFactory
     * @param Json $json
     */
    public function __construct(Data $helperData, WebhookFactory $webhookFactory, Json $json)
    {
        $this->helperData = $helperData;
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
                $this->helperData->processWebhookCron($params, $webhook);
            } catch (LocalizedException $e) {
            }
        }
    }

}
