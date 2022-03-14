<?php

namespace Balancepay\Balancepay\Cron;

use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\WebhookFactory;

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
     * Webhook constructor.
     *
     * @param Data $helperData
     */
    public function __construct(Data $helperData, WebhookFactory $webhookFactory)
    {
        $this->helperData = $helperData;
        $this->webhookFactory = $webhookFactory;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $webhookCollection = $this->webhookFactory->create()->getCollection();
        foreach ($webhookCollection as $webhook) {
            $this->helperData->getConfirmedData($webhook->getContent(), $webhook->getHeader(), true);
        }
    }

}
