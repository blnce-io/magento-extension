<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayRefund\Collection;
use Magento\Sales\Model\Order\CreditmemoFactory;

class RefundFailedProcessor
{
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var BalancepayChargeFactory
     */
    private $balancepayChargeFactory;

    /**
     * RefundCanceledProcessor constructor.
     *
     * @param Config $balancepayConfig
     * @param BalancepayChargeFactory $balancepayChargeFactory
     * @param Collection $collection
     */
    public function __construct(
        BalancepayConfig $balancepayConfig,
        BalancepayChargeFactory $balancepayChargeFactory,
        Collection $collection,
        CreditmemoFactory $creditmemoFactory
    )
    {
        $this->balancepayConfig = $balancepayConfig;
        $this->collection = $collection;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
        $this->creditmemoFactory = $creditmemoFactory;
    }

    /**
     * @param $params
     * @param $jobName
     */
    public function processFailedWebhook($params, $jobName)
    {
        $data = $params[0];
        $json = !empty($data) ? json_decode($data, true) : [];
        $refundId = isset($json['data']['refundId']) ? $json['data']['refundId'] : '';
        if(!$refundId){
            throw new LocalizedException(new Phrase("Refund ID Not Present!"));
        }
        $memoId = $this->collection->addFieldToFilter('refund_id', ['eq' => $refundId])
            ->getFirstItem()->getCreditMemoId();
        if ($memoId) {
            $creditmemoModel = $this->creditmemoFactory->create();
            $creditmemoModel->setData([
                'entity_id' => $memoId,
                'creditmemo_status' => 1
            ]);
            $creditmemoModel->save();
            return true;
        } else {
            throw new LocalizedException(new Phrase("Memo ID Not Present!"));
        }
    }
}
