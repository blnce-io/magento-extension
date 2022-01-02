<?php
namespace Balancepay\Balancepay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BalancepayProduct extends AbstractDb
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('balancepay_product', 'entity_id');
    }
}
