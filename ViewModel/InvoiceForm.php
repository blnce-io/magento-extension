<?php

namespace Balancepay\Balancepay\ViewModel;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class InvoiceForm implements ArgumentInterface
{
    /**
     * InvoiceForm constructor.
     *
     * @param Collection $collection
     * @param RequestInterface $request
     */
    public function __construct(
        Collection $collection,
        RequestInterface $request
    ) {
        $this->collection = $collection;
        $this->request = $request;
    }

    /**
     * EnableCreditMemo
     *
     * @return bool
     */
    public function enableCreditMemo()
    {

        $invoiceId = $this->request->getParam('invoice_id');
        if (!$invoiceId) {
            return true;
        }
        $chargeFlag = $this->collection->getChargeAndStatus($invoiceId);
        if ($chargeFlag) {
            return true;
        }
        return false;
    }
}
