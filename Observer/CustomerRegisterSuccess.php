<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Balancepay\Balancepay\Model\BalanceBuyer;
use Balancepay\Balancepay\Model\Config;

class CustomerRegisterSuccess implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @var BalanceBuyer
     */
    protected $balanceBuyer;

    /**
     * @var Config
     */
    protected $balancepayConfig;

    public function __construct(
        SessionManagerInterface $coreSession,
        BalanceBuyer $balanceBuyer,
        Config $balancepayConfig
    ) {
        $this->_coreSession = $coreSession;
        $this->balanceBuyer = $balanceBuyer;
        $this->balancepayConfig = $balancepayConfig;
    }

    public function execute(Observer $observer)
    {
        try {
            $customerId = $observer->getCustomer()->getId();
            $buyerId = $this->_coreSession->getBalanceBuyerId();
            if ($buyerId && $customerId) {
                $this->balanceBuyer->updateCustomerBalanceBuyerId($buyerId, $customerId);
                $this->_coreSession->unsBalanceBuyerId();
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Customer Register Success - Couldnot assign the buyer to customer');
        }
    }
}
