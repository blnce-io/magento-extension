<?php
namespace Balancepay\Balancepay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Webhook extends AbstractDb
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('webhook_queue', 'entity_id');
    }
}
