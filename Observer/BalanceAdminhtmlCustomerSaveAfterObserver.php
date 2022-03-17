<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Message\ManagerInterface;
use Magento\Indexer\Model\IndexerFactory;

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
     * @var IndexerFactory
     */
    protected $indexFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param ManagerInterface $messageManager
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param IndexerFactory $indexFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceConnection $resource,
        Config $balancepayConfig,
        RequestFactory $requestFactory,
        ManagerInterface $messageManager,
        Customer           $customer,
        CustomerFactory    $customerFactory,
        IndexerFactory $indexFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->_messageManager = $messageManager;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->indexFactory = $indexFactory;
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

        if (!empty($customerId)) {
            $termOptions = !empty($postData['buyer']['term_options']) ?
                implode(',', $postData['buyer']['term_options']) : '';
            $customer = $this->customer->load($customerId);
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('term_options', $termOptions);
            $customer->updateData($customerData);
            $customerResource = $this->customerFactory->create();
            $customerResource->saveAttribute($customer, 'term_options');
        }
        $this->runCustomerGridIndex();
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

    /**
     * RunCustomerGridIndex
     *
     * @return void
     * @throws \Exception
     */
    public function runCustomerGridIndex()
    {
        $indexer = $this->indexFactory->create()->load('customer_grid');
        $indexer->reindexAll();
    }
}
