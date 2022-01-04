<?php
namespace Balancepay\Balancepay\Helper;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @var MpProductCollection
     */
    protected $_mpProductCollectionFactory;

    /**
     * Data constructor.
     * @param MpProductCollection $mpProductCollectionFactory
     */
    public function __construct(
        MpProductCollection $mpProductCollectionFactory
    ) {
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
    }

    /**
     * Get balance Vendors
     *
     * @param string $productId
     * @return string
     */
    public function getBalanceVendors($productId = '')
    {
        return $this->getSellerIdByProductId($productId);
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
