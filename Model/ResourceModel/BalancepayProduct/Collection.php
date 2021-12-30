<?php
namespace Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Balancepay\Balancepay\Model\BalancepayProduct as BalancepayProductModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct as BalancepayProductResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(BalancepayProductModel::class, BalancepayProductResourceModel::class);
    }
}
