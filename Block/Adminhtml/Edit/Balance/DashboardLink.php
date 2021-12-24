<?php
namespace Balancepay\Balancepay\Block\Adminhtml\Edit\Balance;

use \Magento\Backend\Block\Template;
use Balancepay\Balancepay\Model\Config;
use Webkul\Marketplace\Model\SellerFactory;

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
     * @param Template\Context $context
     * @param SellerFactory $sellerModel
     * @param Config $balancepayConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        SellerFactory $sellerModel,
        Config $balancepayConfig,
        array $data = []
    ) {
        $this->sellerModel = $sellerModel;
        $this->balancepayConfig = $balancepayConfig;
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
