<?php
namespace Balancepay\Balancepay\Model;

use Magento\Framework\Model\AbstractModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayRefund as BalancepayRefundResourceModel;

class BalancepayRefund extends AbstractModel
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BalancepayRefundResourceModel::class);
    }
}
