<?php
namespace Balancepay\Balancepay\Observer;

use Balancepay\Balancepay\Model\BalancepayRefundFactory;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderCreditmemoSaveAfter implements ObserverInterface
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var BalancepayRefundFactory
     */
    private $balancepayRefundFactory;

    public function __construct(
        Collection $collection,
        RequestFactory $requestFactory,
        BalancepayRefundFactory $balancepayRefundFactory
    ) {
        $this->collection = $collection;
        $this->requestFactory = $requestFactory;
        $this->balancepayRefundFactory = $balancepayRefundFactory;
    }

    public function execute(Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $creditMemoId = $creditMemo->getId();
        $total = $creditMemo->getBaseGrandTotal();
        $invoiceId = $creditMemo->getInvoiceId();
        $chargeId = $this->collection->addFieldToFilter('invoice_id', ['eq' => $invoiceId])->getFirstItem()->getChargeId();
        $response = $this->requestFactory
            ->create(RequestFactory::REFUND_REQUEST_METHOD)
            ->setRequestMethod('charges/'.$chargeId.'/refunds')
            ->setTopic('refunds')
            ->setAmount($total)
            ->setChargeId($chargeId)
            ->process();
        $refundId = $response['id'];
        $balancepayRefundModel = $this->balancepayRefundFactory->create();
        $balancepayRefundModel->setData([
            'credit_memo_id' => $creditMemoId,
            'refund_id' => $refundId
        ]);
        $balancepayRefundModel->save();
        return $this;
    }
}
