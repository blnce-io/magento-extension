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
     * @var SellerFactory
     */
    private $sellerModel;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var SellerFactory
     */
    private $sellerFactory;

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
     * Get Balancepay dashboard URL
     *
     * @return string
     */
    public function getBalancePayDashboardUrl()
    {
        $vendorId = $this->getBalanceVendorId();
        $balancePayDashboardUrl = $this->balancepayConfig->getBalanceDashboardUrl();
        if ($vendorId) {
            return $balancePayDashboardUrl . '/vendors/' . $vendorId;
        }
        return $balancePayDashboardUrl;
    }

    /**
     * Get balancepaystatus
     *
     * @return bool
     */
    public function getBalancePayStatus()
    {
        $customerId = $this->getRequest()->getParam('id');
        $sellerCollection = $this->sellerModel->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $customerId)
            ->addFieldToFilter('is_seller', 1);
        if (isset($sellerCollection) && count($sellerCollection) > 0) {
            foreach ($sellerCollection as $collection) {
                if ($collection->getPayouts()) {
                    return true;
                }
            }
        }
        return false;
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
                    $collection->setPayouts(true);
                }
            } else {
                if (!empty($collection['balance_vendor_id'])) {
                    $collection->setPayouts(false);
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
     * @return bool
     */
    public function getBalanceVendorId()
    {
        $customerId = $this->getRequest()->getParam('id');
        $collection = $this->sellerModel->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $customerId)
            ->addFieldToFilter('is_seller', 1);

        foreach ($collection as $seller) {
            if ($seller->getBalanceVendorId() != '') {
                return $seller->getBalanceVendorId();
            }
        }
        return false;
    }
}
