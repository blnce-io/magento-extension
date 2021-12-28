<?php
namespace Balancepay\Balancepay\Helper;

use \Webkul\Marketplace\Helper\Data as WebkulHelper;
use \Webkul\Marketplace\Model\SellerFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
/**
 * Class Data
 * @package Balancepay\Balancepay\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @param WebkulHelper $data
     * @param SellerFactory $sellerFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        WebkulHelper $data,
        SellerFactory $sellerFactory,
        CollectionFactory $collectionFactory)
    {
        $this->data = $data;
        $this->sellerFactory = $sellerFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param $sellerId
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
     * @param string $productId
     * @return string
     */
    public function getBalanceVendors($productId = '')
    {
        $transactionColl = $this->collectionFactory->create()
            ->addFieldToFilter(
                'mageproduct_id',
                $productId
            );
        $sellerId = $transactionColl->getFirstItem()->getSellerId();
        $balanceVendorId = $this->getVendorId($sellerId);
        return $balanceVendorId;
    }
}
