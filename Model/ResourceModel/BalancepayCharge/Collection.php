<?php
namespace Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Balancepay\Balancepay\Model\BalancepayCharge as BalancepayChargeModel;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge as BalancepayChargeResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BalancepayChargeModel::class, BalancepayChargeResourceModel::class);
    }

    public function getChargeAndStatus($invoiceId) {
        $chargeItem = $this->addFieldToFilter('invoice_id', ['eq' => $invoiceId])->getFirstItem()->getData();
        return $chargeItem['charge_id'] && ($chargeItem['status'] == 'charged');
    }

    public function getChargeId($invoiceId) {
        $chargeItem = $this->addFieldToFilter('invoice_id', ['eq' => $invoiceId])->getFirstItem();
        if (isset($chargeItem['charge_id'])) {
            return $chargeItem['charge_id'];
        }
        return '';
    }
}
