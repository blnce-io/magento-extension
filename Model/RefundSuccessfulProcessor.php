<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayRefund\Collection;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;

class RefundSuccessfulProcessor
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
        Creditmemo $creditmemo,
        CreditmemoRepositoryInterface $creditmemoRepository
    )
    {
        $this->balancepayConfig = $balancepayConfig;
        $this->collection = $collection;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
        $this->creditmemo = $creditmemo;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    /**
     * @param $params
     * @param $jobName
     */
    public function processSuccessfulWebhook($params, $jobName)
    {
        $data = $params;
        $json = !empty($data) ? $data : [];
        $refundId = isset($json['refundId']) ? $json['refundId'] : '';
        if (!$refundId) {
            throw new LocalizedException(new Phrase("Refund ID Not Present!"));
        }
        $memoId = $this->collection->addFieldToFilter('refund_id', ['eq' => $refundId])
            ->getFirstItem()->getCreditMemoId();
        if ($memoId) {
            $memoData = $this->creditmemoRepository->get($memoId);
            $memoData->addData(['state' => Creditmemo::STATE_REFUNDED]);
            $this->creditmemoRepository->save($memoData);
            return true;
        } else {
            throw new LocalizedException(new Phrase("Memo ID Not Present!"));
        }
    }
}
