<?php
namespace Balancepay\Balancepay\Model;

use Magento\Framework\Model\AbstractModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge as BalancepayChargeResourceModel;

class BalancepayCharge extends AbstractModel
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BalancepayChargeResourceModel::class);
    }
}
