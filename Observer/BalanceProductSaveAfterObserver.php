<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Balancepay\Balancepay\Model\BalancepayProductFactory as MpProductFactory;

class BalanceProductSaveAfterObserver implements ObserverInterface
{
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
     * BalanceProductSaveAfterObserver constructor.
     * @param DateTime $date
     * @param ManagerInterface $messageManager
     * @param MpProductFactory $mpProductFactory
     */
    public function __construct(
        DateTime $date,
        ManagerInterface $messageManager,
        MpProductFactory $mpProductFactory
    ) {
        $this->_date = $date;
        $this->messageManager = $messageManager;
        $this->mpProductFactory = $mpProductFactory;
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
                if ($productCollection->getSize()) {
                    $productCollection->getFirstItem()->setData('vendor_id', $sellerId)->save();
                } else {
                    $mpProductModel = $this->mpProductFactory->create();
                    $mpProductModel->setProductId($productId);
                    $mpProductModel->setVendorId($sellerId);
                    $mpProductModel->setCreatedAt($this->_date->gmtDate());
                    $mpProductModel->setUpdatedAt($this->_date->gmtDate());
                    $mpProductModel->save();
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }
}
