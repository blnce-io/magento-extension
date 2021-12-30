<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Balancepay\Balancepay\Model\BalancepayProductFactory as MpProductFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;

class BalanceProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var MpProductFactory
     */
    protected $mpProductFactory;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param MpProductFactory $mpProductFactory
     * @param MpHelper $mpHelper
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        MpProductFactory $mpProductFactory,
        MpHelper $mpHelper
    )
    {
        $this->_collectionFactory = $collectionFactory;
        $this->_date = $date;
        $this->messageManager = $messageManager;
        $this->mpProductFactory = $mpProductFactory;
        $this->mpHelper = $mpHelper;
    }

    /**
     * Product save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $assginVendorData = $product->getAssignVendor();
            $productId = $observer->getProduct()->getId();

            $productCollection = $this->mpProductFactory->create()
                ->getCollection()
                ->addFieldToFilter(
                    'product_id',
                    $productId
                );
            if (is_array($assginVendorData) &&
                isset($assginVendorData['vendor_id']) &&
                $assginVendorData['vendor_id'] != ''
            ) {
                $sellerId = $assginVendorData['vendor_id'];
            }
            if ($productCollection->getSize()) {
                foreach ($productCollection as $product) {
                    $product->setVendorId($sellerId)->save();
                }
            } else {
                $mpProductModel = $this->mpProductFactory->create();
                $mpProductModel->setProductId($productId);
                $mpProductModel->setVendorId($sellerId);
                $mpProductModel->setCreatedAt($this->_date->gmtDate());
                $mpProductModel->setUpdatedAt($this->_date->gmtDate());
                $mpProductModel->save();
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger(
                "Observer_CatalogProductSaveAfterObserver execute : " . $e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }
    }
}
