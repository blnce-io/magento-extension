<?php
namespace Balancepay\Balancepay\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Balancepay\Balancepay\Model\Queue as QueueModel;
use Balancepay\Balancepay\Model\ResourceModel\Queue as QueueResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(QueueModel::class, QueueResourceModel::class);
    }
}
