<?php
namespace Balancepay\Balancepay\Model\ResourceModel\Webhook;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Balancepay\Balancepay\Model\Webhook as WebhookModel;
use Balancepay\Balancepay\Model\ResourceModel\Webhook as WebhookResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(WebhookModel::class, WebhookResourceModel::class);
    }
}
