<?php

namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Message\ManagerInterface;

class BalanceAdminhtmlCustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * BalanceAdminhtmlCustomerSaveAfterObserver constructor.
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceConnection $resource,
        Config $balancepayConfig,
        RequestFactory $requestFactory,
        ManagerInterface $messageManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->_messageManager = $messageManager;
    }

    /**
     * Admin customer save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        $customerId = $customer->getId();
        $postData = $observer->getRequest()->getPostValue();

        if (isset($postData['is_seller_add'])) {
            $this->createBalancePayVendor($postData['customer']['email']);
        }
        if ($this->isSeller($customerId)) {
            $vendorData = $postData['vendor']['data'] ?? [];
            if (!empty($vendorData)) {
                if (isset($vendorData['balance_vendor_id']) && $vendorData['balance_vendor_id'] != '') {
                    $columnData['balance_vendor_id'] = $vendorData['balance_vendor_id'];
                }
                $this->connection->update(
                    $this->resource->getTableName('marketplace_userdata'),
                    $columnData,
                    "`seller_id`= $customerId"
                );
            }
        }
        return $this;
    }

    /**
     * Check is seller
     *
     * @param string $customerId
     * @return bool
     */
    public function isSeller($customerId = '')
    {
        $model = $this->collectionFactory->create()
            ->addFieldToFilter('seller_id', $customerId)->getFirstItem()->getData();
        if (isset($model) && count($model) > 0) {
            return $model['is_seller'];
        }
        return false;
    }

    /**
     * Create Balance vendor
     *
     * @param string $postCustEmail
     * @throws LocalizedException
     */
    public function createBalancePayVendor($postCustEmail = '')
    {
        try {
            if ($this->balancepayConfig->getIsBalanaceVendorRegistry()) {
                $response = $this->requestFactory
                    ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                    ->setRequestMethod('vendors')
                    ->setTopic('vendors')
                    ->process();

                $emails = array_column($response, 'email');
                if (!in_array($postCustEmail, $emails)) {
                    $this->requestFactory
                        ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                        ->setRequestMethod('vendors')
                        ->setTopic('create-vendors')
                        ->process();
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e->getMessage());
        }
    }
}
