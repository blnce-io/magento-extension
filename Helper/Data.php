<?php
namespace Balancepay\Balancepay\Helper;

use Magento\Framework\Message\ManagerInterface;
use \Webkul\Marketplace\Model\SellerFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Data constructor.
     *
     * @param SellerFactory $sellerFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        SellerFactory $sellerFactory,
        MpProductCollection $mpProductCollectionFactory,
        CollectionFactory $collectionFactory,
        ManagerInterface $messageManager
    ) {
        $this->sellerFactory = $sellerFactory;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Get Vendor Id
     *
     * @param int $sellerId
     * @return string
     */
    public function getVendorId($sellerId)
    {
        $balancepay_vendor_id = '';
        $collection = $this->sellerFactory->create()
            ->getCollection()
            ->addFieldToSelect('balance_vendor_id')
            ->addFieldToFilter('is_seller', \Webkul\Marketplace\Model\Seller::STATUS_ENABLED)
            ->addFieldToFilter('seller_id', $sellerId)->getFirstItem()->getData();
        if (!empty($collection['balance_vendor_id'])) {
            return $collection['balance_vendor_id'];
        }
        return $balancepay_vendor_id;
    }

    /**
     * Get balance Vendors
     *
     * @param string $productId
     * @return string
     */
    public function getBalanceVendor($productId = '')
    {
        $balanceVendorId = '';
        try {
            $transactionColl = $this->collectionFactory->create()
                ->addFieldToFilter(
                    'mageproduct_id',
                    $productId
                );
            $sellerId = $transactionColl->getFirstItem()->getSellerId();
            $balanceVendorId = $this->getVendorId($sellerId);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        if (empty($balanceVendorId)) {
            $balanceVendorId = $this->getSellerIdByProductId($productId);
        }

        return $balanceVendorId;
    }

    /**
     * Return the seller Id by product id.
     *
     * @param string $productId
     * @return mixed
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $sellerId = $collection->getFirstItem()->getVendorId();
        return $sellerId;
    }

    /**
     * Valid domain
     *
     * @param string $domainName
     * @return bool
     */
    public function isValidDomain($domainName): bool
    {
        if (preg_match(
            '/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/',
            $domainName
        )) {
            return true;
        }
        return false;
    }
}
