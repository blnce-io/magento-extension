<?php
namespace Balancepay\Balancepay\Model;

use Magento\Framework\Model\AbstractModel;
use Balancepay\Balancepay\Model\ResourceModel\Queue as QueueResourceModel;

class Queue extends AbstractModel
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(QueueResourceModel::class);
    }
}
