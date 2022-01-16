<?php

namespace Balancepay\Balancepay\Block\Adminhtml\Edit\Balance;

use Magento\Backend\Block\Template;
use Balancepay\Balancepay\Model\Config;
use Webkul\Marketplace\Model\SellerFactory;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;

class DashboardLink extends Template
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'balance/dashboardlink.phtml';

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * DashboardLink constructor.
     * @param Template\Context $context
     * @param SellerFactory $sellerModel
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param SellerFactory $sellerFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SellerFactory $sellerModel,
        Config $balancepayConfig,
        RequestFactory $requestFactory,
        SellerFactory $sellerFactory,
        array $data = []
    ) {
        $this->sellerModel = $sellerModel;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->sellerFactory = $sellerFactory;
        parent::__construct($context, $data);
    }

    /**
     * Is Vendor Id Set
     *
     * @return bool
     */
    public function isVendorIdSet()
    {
        return (bool)$this->getBalanceVendorId();
    }

    /**
     * Get Balance Pay Dashboard URL
     *
     * @return string
     */
    public function getBalancePayDashboardUrl()
    {
        $vendorId = $this->getBalanceVendorId();
        $balancePayDashboardUrl = $this->balancepayConfig->getBalanceDashboardUrl();
        return $balancePayDashboardUrl . '/vendors/' . $vendorId;
    }

    /**
     * Get balancepaystatus
     *
     * @return string
     */
    public function getBalancePayStatus()
    {
        $customerId = $this->getRequest()->getParam('id');
        $collection = $this->sellerModel->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $customerId)
            ->addFieldToFilter('is_seller', 1);
        if (isset($collection) && count($collection) > 0) {
            foreach ($collection as $col) {
                if ($col->getPayouts()) {
                    return 'Enabled';
                }
            }
        }
        return 'Disabled';
    }

    /**
     * Get Balance Vendor
     *
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBalanceVendor()
    {
        $vendorId = $this->getBalanceVendorId();
        if ($vendorId) {
            $response = $this->requestFactory
                ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                ->setRequestMethod('vendors/' . $vendorId)
                ->setTopic('vendors')
                ->process();
            $collection = $this->sellerFactory->create()
                ->getCollection()
                ->addFieldToFilter('is_seller', \Webkul\Marketplace\Model\Seller::STATUS_ENABLED)
                ->addFieldToFilter('balance_vendor_id', $response['sellerInfo']['id'])->getFirstItem();

            if (!empty($response['paymentData']['banks']) && !empty($response['sellerInfo']['address'])) {
                if (!empty($collection['balance_vendor_id'])) {
                    $collection->setPayouts(1);
                }
            } else {
                if (!empty($collection['balance_vendor_id'])) {
                    $collection->setPayouts(0);
                }
            }
            $collection->save();

            if (isset($response['sellerInfo']['name'])) {
                return $response['sellerInfo']['name'];
            }
        }
        return '';
    }

    /**
     * Get Balance Vendor Id
     *
     * @return int
     */
    public function getBalanceVendorId()
    {
        $vendorId = 0;
        $customerId = $this->getRequest()->getParam('id');
        $collection = $this->sellerModel->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $customerId)
            ->addFieldToFilter('is_seller', 1);

        foreach ($collection as $col) {
            if ($col->getBalanceVendorId() != '') {
                return $col->getBalanceVendorId();
            }
        }
        return $vendorId;
    }
}
