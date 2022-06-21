<?php
namespace Balancepay\Balancepay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BalancepayCharge extends AbstractDb
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('balance_charges', 'entity_id');
    }
}
