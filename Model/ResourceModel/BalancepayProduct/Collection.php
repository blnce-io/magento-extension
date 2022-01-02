<?php
namespace Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Balancepay\Balancepay\Model\BalancepayProduct as BalancepayProductModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct as BalancepayProductResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BalancepayProductModel::class, BalancepayProductResourceModel::class);
    }
}
