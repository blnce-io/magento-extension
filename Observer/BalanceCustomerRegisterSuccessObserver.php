<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Message\ManagerInterface;

/**
 * Webkul Marketplace CustomerRegisterSuccessObserver Observer.
 */
class BalanceCustomerRegisterSuccessObserver implements ObserverInterface
{

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * BalanceCustomerRegisterSuccessObserver constructor.
     * @param ManagerInterface $messageManager
     * @param MpHelper $mpHelper
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        ManagerInterface $messageManager,
        MpHelper $mpHelper,
        Config $balancepayConfig,
        RequestFactory $requestFactory
    ) {
        $this->_messageManager = $messageManager;
        $this->mpHelper = $mpHelper;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Observer to create Balance vendor after registration
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if (!empty($paramData['is_seller']) && $paramData['is_seller'] == 1) {
                $this->createBalancePayVendor();
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger(
                "Observer_BalanceCustomerRegisterSuccessObserver execute : " . $e->getMessage()
            );
            $this->_messageManager->addError($e->getMessage());
        }
    }

    /**
     * Create Balance vendor
     *
     * @throws LocalizedException
     */
    public function createBalancePayVendor()
    {
        if ($this->balancepayConfig->getIsBalanaceVendorRegistry()) {
            try {
                $this->requestFactory
                    ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                    ->setRequestMethod('vendors')
                    ->setTopic('create-vendors')
                    ->process();
            } catch (LocalizedException $e) {
                $this->_messageManager->addExceptionMessage($e);
            }
        }
    }
}
