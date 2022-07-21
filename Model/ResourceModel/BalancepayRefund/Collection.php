<?php
namespace Balancepay\Balancepay\Model\ResourceModel\BalancepayRefund;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Balancepay\Balancepay\Model\BalancepayRefund as BalancepayRefundModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayRefund as BalancepayRefundResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BalancepayRefundModel::class, BalancepayRefundResourceModel::class);
    }
}
