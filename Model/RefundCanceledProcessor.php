<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayRefund\Collection;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;

class RefundCanceledProcessor
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
     * @var Collection
     */
    private $collection;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var Creditmemo
     */
    private $creditmemo;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * RefundCanceledProcessor constructor.
     *
     * @param Config $balancepayConfig
     * @param BalancepayChargeFactory $balancepayChargeFactory
     * @param Collection $collection
     * @param Creditmemo $creditmemo
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(
        BalancepayConfig $balancepayConfig,
        BalancepayChargeFactory $balancepayChargeFactory,
        Collection $collection,
        Creditmemo $creditmemo,
        CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->collection = $collection;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
        $this->creditmemo = $creditmemo;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    /**
     * ProcessCanceledWebhook
     *
     * @param string|mixed|array $params
     * @param string|mixed|array $jobName
     */
    public function processCanceledWebhook($params, $jobName)
    {
        $data = $params[0];
        $json = !empty($data) ? json_decode($data, true) : [];
        $refundId = isset($json['refundId']) ? $json['refundId'] : '';
        if (!$refundId) {
            throw new LocalizedException(new Phrase("Refund ID Not Present!"));
        }
        $memoId = $this->collection->addFieldToFilter('refund_id', ['eq' => $refundId])
            ->getFirstItem()->getCreditMemoId();
        if ($memoId) {
            $memoData = $this->creditmemoRepository->get($memoId);
            $memoData->addData(['state' => Creditmemo::STATE_CANCELED]);
            $this->creditmemoRepository->save($memoData);
            return true;
        } else {
            throw new LocalizedException(new Phrase("Memo ID Not Present!"));
        }
    }
}
