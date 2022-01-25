<?php
namespace Balancepay\Balancepay\Helper;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\App\Cache\Type\Config;

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
        MpProductCollection $mpProductCollectionFactory,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $appConfig,
        MessageManagerInterface $messageManager
    ) {
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $appConfig;
        $this->messageManager = $messageManager;
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

    /**
     * CleanConfigCache
     *
     * @return $this
     */
    public function cleanConfigCache()
    {
        try {
            $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            $this->appConfig->reinit();
        } catch (\Exception $e) {
            $this->messageManager->addNoticeMessage(__('For some reason,
            Balance (payment) couldn\'t clear your config cache,
            please clear the cache manually. (Exception message: %1)', $e->getMessage()));
        }
        return $this;
    }
}
