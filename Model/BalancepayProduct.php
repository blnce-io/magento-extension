<?php
namespace Balancepay\Balancepay\Model;

use Magento\Framework\Model\AbstractModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct as BalancepayProductResourceModel;

class BalancepayProduct extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(BalancepayProductResourceModel::class);
    }
}
