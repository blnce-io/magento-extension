<?php

namespace Balancepay\Balancepay\Plugin\Sales\Model;

use Magento\Sales\Model\Order;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Magento\Framework\App\RequestInterface;

class OrderPlugin
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * OrderPlugin constructor.
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
     * AfterCanCreditmemo
     *
     * @param Order $subject
     * @param mixed|array|string|null $result
     * @return bool
     */
    public function afterCanCreditmemo(
        Order $subject,
        $result
    ) {
        $invoiceId = $this->request->getParam('invoice_id');
        if (!$invoiceId) {
            return true;
        }
        $chargeFlag = $this->collection->getChargeAndStatus($invoiceId);
        if ($result && $chargeFlag) {
            return true;
        }
        return false;
    }
}
