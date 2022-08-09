<?php
namespace Balancepay\Balancepay\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items;

class CreditNote implements ArgumentInterface
{
    /**
     * @var Items
     */
    private $item;

    public function __construct(
        Items $item
    ) {
        $this->item = $item;
    }

    public function checkBalanceMethod() {
        $method = $this->item->getOrder()->getPayment()->getMethod();
        if (strtolower($method) == 'balancepay') {
            return true;
        }
        return false;
    }
}
