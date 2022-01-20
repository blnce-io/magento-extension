<?php
namespace Balancepay\Balancepay\Helper;

use Magento\Framework\Message\ManagerInterface;
use Webkul\Marketplace\Helper\Data as MpDataHelper;
use \Webkul\Marketplace\Model\SellerFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use \Magento\Framework\App\Helper\AbstractHelper;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as sellerCollectionFactory;

class Data extends AbstractHelper
{

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $_sellerCollectionFactory;

    /**
     * @param SellerFactory $sellerFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     * @param sellerCollectionFactory $sellerCollectionFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        SellerFactory $sellerFactory,
        MpProductCollection $mpProductCollectionFactory,
        CollectionFactory $collectionFactory,
        ManagerInterface $messageManager,
        sellerCollectionFactory $sellerCollectionFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->sellerFactory = $sellerFactory;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->messageManager = $messageManager;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        $this->_jsonHelper = $jsonHelper;
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
    private function isValidDomain($domainName): bool
    {
        if (preg_match(
            '/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/',
            $domainName
        )) {
            return true;
        }
        return false;
    }

    /**
     * Check shop url
     *
     * @param string $profileUrl
     * @return string
     */
    public function checkShopUrl($profileUrl)
    {
        if ($profileUrl == "" || $profileUrl == MpDataHelper::MARKETPLACE_ADMIN_URL) {
            return $this->_jsonHelper->jsonEncode(true);
        } else {
            $collection = $this->_sellerCollectionFactory->create();
            $collection->addFieldToFilter('shop_url', $profileUrl);
            if (!$collection->getSize() && $this->isValidDomain($profileUrl)) {
                return $this->_jsonHelper->jsonEncode(false);
            } else {
                return $this->_jsonHelper->jsonEncode(true);
            }
        }
    }
}
