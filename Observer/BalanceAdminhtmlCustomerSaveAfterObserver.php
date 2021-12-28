<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

class BalanceAdminhtmlCustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
    }

    /**
     * admin customer save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        $customerid = $customer->getId();
        $postData = $observer->getRequest()->getPostValue();
        if ($this->isSeller($customerid)) {
            if (isset($postData['vendor']['data']['balance_vendor_id']) && $postData['vendor']['data']['balance_vendor_id'] != '') {
                $columnData['balance_vendor_id'] = $postData['vendor']['data']['balance_vendor_id'];
            }
            $this->connection->update(
                $this->resource->getTableName('marketplace_userdata'),
                $columnData,
                "`seller_id`= $customerid"
            );
        }
        return $this;
    }

    /**
     * @param $customerid
     * @return int
     */
    public function isSeller($customerid)
    {
        $sellerStatus = 0;
        $model = $this->collectionFactory->create()
            ->addFieldToFilter('seller_id', $customerid)
            ->addFieldToFilter('store_id', 0);
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }
        return $sellerStatus;
    }
}
