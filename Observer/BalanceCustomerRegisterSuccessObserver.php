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

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Event\Observer;
use Zend_Log_Exception;

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
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * BalanceCustomerRegisterSuccessObserver constructor.
     *
     * @param ManagerInterface $messageManager
     * @param MpHelper $mpHelper
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        ManagerInterface $messageManager,
        MpHelper $mpHelper,
        Config $balancepayConfig,
        RequestFactory $requestFactory,
        ResourceConnection $resource
    ) {
        $this->_messageManager = $messageManager;
        $this->mpHelper = $mpHelper;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
    }

    /**
     * Observer to create Balance vendor after registration
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if (!empty($paramData['is_seller']) && $paramData['is_seller'] == 1) {
                $customerId = $observer->getCustomer()->getId();
                $this->createBalancePayVendor($customerId);
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
     * @param int $customerId
     * @throws Zend_Log_Exception
     */
    public function createBalancePayVendor($customerId)
    {
        if ($this->balancepayConfig->getIsBalanaceVendorRegistry() && $customerId) {
            try {
                $response = $this->requestFactory
                    ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                    ->setRequestMethod('vendors')
                    ->setTopic('create-vendors')
                    ->process();
                if (!empty($response['vendor']['id'])) {
                    $columnData['balance_vendor_id'] = $response['vendor']['id'];
                    $this->connection->update(
                        $this->resource->getTableName('marketplace_userdata'),
                        $columnData,
                        "`seller_id`= $customerId"
                    );
                }
            } catch (LocalizedException $e) {
                $this->_messageManager->addExceptionMessage($e);
            }
        }
    }
}
