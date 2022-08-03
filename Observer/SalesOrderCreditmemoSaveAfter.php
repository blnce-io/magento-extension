<?php
namespace Balancepay\Balancepay\Observer;

use Balancepay\Balancepay\Model\BalancepayRefundFactory;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

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

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Constructor
     *
     * @param Collection $collection
     * @param RequestFactory $requestFactory
     * @param BalancepayRefundFactory $balancepayRefundFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Collection $collection,
        RequestFactory $requestFactory,
        BalancepayRefundFactory $balancepayRefundFactory,
        ManagerInterface $messageManager
    ) {
        $this->collection = $collection;
        $this->requestFactory = $requestFactory;
        $this->balancepayRefundFactory = $balancepayRefundFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $creditMemoId = $creditMemo->getId();
        $total = $creditMemo->getBaseGrandTotal();
        $invoiceId = $creditMemo->getInvoiceId();
        $message = "We cannot automatically initiate a refund on Balance as this is a offline refund request. Please initiate a refund directly from the Balance dashboard.";
        if ($invoiceId) {
            $chargeId = $this->collection->addFieldToFilter('invoice_id', ['eq' => $invoiceId])
                ->getFirstItem()->getChargeId();
            $message = "We cannot automatically initiate a refund for this invoice.  Please initiate a refund directly from the Balance dashboard.";
            if ($chargeId) {
                $response = $this->requestFactory
                    ->create(RequestFactory::REFUND_REQUEST_METHOD)
                    ->setRequestMethod('charges/' . $chargeId . '/refunds')
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
                $message = "Refund request has been sent to Balancepay.";
            }
        }
        $this->messageManager->addNoticeMessage(__($message));
        return $this;
    }
}
