<?php
namespace Balancepay\Balancepay\Helper;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var MpProductCollection
     */
    protected $_mpProductCollectionFactory;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var Context
     */
    protected $appContext;

    /**
     * @param MpProductCollection $mpProductCollectionFactory
     * @param TypeListInterface $cacheTypeList
     * @param MessageManagerInterface $messageManager
     * @param Context $appContext
     */
    public function __construct(
        MpProductCollection $mpProductCollectionFactory,
        TypeListInterface $cacheTypeList,
        MessageManagerInterface $messageManager,
        Context $appContext
    ) {
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->cacheTypeList = $cacheTypeList;
        $this->messageManager = $messageManager;
        $this->appContext = $appContext;
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
     * GetCustomerSessionId
     *
     * @return mixed
     */
    public function getCustomerSessionId()
    {
        return $this->appContext->getValue('customer_id');
    }
}
