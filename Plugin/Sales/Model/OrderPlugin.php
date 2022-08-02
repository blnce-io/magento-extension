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

    public function __construct(
        Collection $collection,
        RequestInterface $request
    ) {
        $this->collection = $collection;
        $this->request = $request;
    }
    public function afterCanCreditmemo(
        Order $subject,
        $result
    ) {
        $invoiceId = $this->request->getParam('invoice_id');
        if(!$invoiceId) {
            return true;
        }
        $chargeId = $this->collection->addFieldToFilter('invoice_id', ['eq' => $invoiceId])
            ->getFirstItem()->getChargeId();
        if (!$chargeId) {
            return false;
        }
        return $result;
    }
}
