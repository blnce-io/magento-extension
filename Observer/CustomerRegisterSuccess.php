<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Balancepay\Balancepay\Model\BalanceBuyer;

class CustomerRegisterSuccess implements ObserverInterface
{
    public function __construct(
        SessionManagerInterface $coreSession,
        BalanceBuyer $balanceBuyer
    ) {
        $this->_coreSession = $coreSession;
        $this->balanceBuyer = $balanceBuyer;
    }

    public function execute(Observer $observer)
    {
        try {
            $buyerId = $this->_coreSession->getBalanceBuyerId();
            $this->balanceBuyer->updateCustomerBalanceBuyerId($buyerId);
        } catch (\Exception $e) {

        }
    }
}
