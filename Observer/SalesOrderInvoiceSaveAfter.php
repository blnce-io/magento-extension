<?php
namespace Balancepay\Balancepay\Observer;

use Balancepay\Balancepay\Model\BalancepayChargeFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

class SalesOrderInvoiceSaveAfter implements ObserverInterface
{
    /**
     * @var BalancepayChargeFactory
     */
    private $balancepayChargeFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * Constructor
     *
     * @param Registry $registry
     * @param BalancepayChargeFactory $balancepayChargeFactory
     */
    public function __construct(
        Registry $registry,
        BalancepayChargeFactory $balancepayChargeFactory
    ) {
        $this->registry = $registry;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return $this|void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $invoiceId = $invoice->getId();
        $chargeId = $this->registry->registry('charge_id');
        $balancepayChargeModel = $this->balancepayChargeFactory->create();
        $balancepayChargeModel->setData([
            'charge_id' => $chargeId,
            'invoice_id' => $invoiceId
        ]);
        $balancepayChargeModel->save();
        return $this;
    }
}
