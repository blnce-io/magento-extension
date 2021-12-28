<?php
namespace Balancepay\Balancepay\Block\Adminhtml\Edit\Balance;

use Magento\Backend\Block\Template;
use Balancepay\Balancepay\Model\Config;
use Webkul\Marketplace\Model\SellerFactory;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;

/**
 * DashboardLink Class
 */
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
     * @param Template\Context $context
     * @param SellerFactory $sellerModel
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SellerFactory $sellerModel,
        Config $balancepayConfig,
        RequestFactory $requestFactory,
        array $data = []
    ) {
        $this->sellerModel = $sellerModel;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isVendorIdSet() {
        return (bool) $this->getBalanceVendorId();
    }

    /**
     * @return string
     */
    public function getBalancePayDashboardUrl() {
        $vendorId = $this->getBalanceVendorId();
        $balancePayDashboardUrl = $this->balancepayConfig->getBalanceDashboardUrl();
        return $balancePayDashboardUrl.'/vendors/'.$vendorId;
    }

    public function getBalanceVendor() {
        $vendorId = $this->getBalanceVendorId();
        if ($vendorId) {
            $response = $this->requestFactory
                ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                ->setRequestMethod('vendors/' . $vendorId)
                ->setTopic('vendors')
                ->process();
            
            if (isset($response['sellerInfo']['name'])) {
                return $response['sellerInfo']['name'];
            }
        }
        return '';
    }

    /**
     * @return void
     */
    public function getBalanceVendorId() {
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
        return;
    }
}
