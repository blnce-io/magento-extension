<?php
namespace Balancepay\Balancepay\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Webkul\Marketplace\Helper\Data as WebkulHelper;
use \Webkul\Marketplace\Model\SellerFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;

class Data extends AbstractHelper
{
    /**
     * @param WebkulHelper $data
     * @param SellerFactory $sellerFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        WebkulHelper $data,
        SellerFactory $sellerFactory,
        MpProductCollection $mpProductCollectionFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->data = $data;
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
        $storeId = $this->data->getCurrentStoreId();
        $collection = $this->sellerFactory->create()
            ->getCollection()
            ->addFieldToFilter('is_seller', \Webkul\Marketplace\Model\Seller::STATUS_ENABLED)
            ->addFieldToFilter(
                ['store_id', 'store_id'],
                [
                    ['eq' => $storeId],
                    ['eq' => 0]
                ]
            )
            ->addFieldToFilter('seller_id', $sellerId);
        if (count($collection) > 0) {
            foreach ($collection as $sellerData) {
                $balancepay_vendor_id = $sellerData->getBalanceVendorId();
            }
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
