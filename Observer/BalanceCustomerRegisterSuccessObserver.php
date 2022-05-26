<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session;
use Balancepay\Balancepay\Model\BalanceBuyer;

class BalanceCustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var BalanceBuyer
     */
    private $balanceBuyer;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Contructor
     *
     * @param ManagerInterface $messageManager
     * @param Config $balancepayConfig
     * @param BalanceBuyer $balanceBuyer
     * @param Session $customerSession
     */
    public function __construct(
        ManagerInterface $messageManager,
        Config $balancepayConfig,
        BalanceBuyer $balanceBuyer,
        Session $customerSession
    ) {
        $this->_messageManager = $messageManager;
        $this->balancepayConfig = $balancepayConfig;
        $this->balanceBuyer = $balanceBuyer;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if ($this->balancepayConfig->isActive() && !empty($paramData['email'])) {
                $buyerId = $this->customerSession->getCustomer()->getBuyerId() ?? '';
                if (empty($buyerId)) {
                    $params = [
                        'first_name' => $paramData['firstname'],
                        'last_name' => $paramData['lastname'],
                        'email' => $paramData['email']
                    ];
                    $this->balanceBuyer->createBuyer($params);
                }
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
    }
}
