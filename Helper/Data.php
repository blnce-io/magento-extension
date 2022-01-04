<?php
namespace Balancepay\Balancepay\Helper;

use \Webkul\Marketplace\Model\SellerFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @param SellerFactory $sellerFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        SellerFactory $sellerFactory,
        MpProductCollection $mpProductCollectionFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->sellerFactory = $sellerFactory;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->collectionFactory = $collectionFactory;
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
    public function getBalanceVendors($productId = '')
    {
        $balanceVendorId = '';
        $transactionColl = $this->collectionFactory->create()
            ->addFieldToFilter(
                'mageproduct_id',
                $productId
            );
        $sellerId = $transactionColl->getFirstItem()->getSellerId();
        $balanceVendorId = $this->getVendorId($sellerId);
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
}
