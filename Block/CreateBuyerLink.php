<?php

namespace Balancepay\Balancepay\Block;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Element\Html\Link;
use Balancepay\Balancepay\Helper\Data as BalancepayHelper;
use Magento\Setup\Exception;
use Webkul\Marketplace\Helper\Data;

/**
 * Class CreateBuyerLink
 *
 * Balancepay\Balancepay\Block
 */
class CreateBuyerLink extends Link
{
    /**
     * @var string
     */
    protected $_template = 'Balancepay_Balancepay::buyer/createbuyer.phtml';

    /**
     * @var BalancepayHelper
     */
    protected $balancepayHelper;

    /**
     * @var Data
     */
    private $webkulHelper;

    /**
     * CreateBuyerLink constructor.
     *
     * @param Template\Context $context
     * @param BalancepayHelper $balancepayHelper
     * @param Data $webkulHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        BalancepayHelper $balancepayHelper,
        Data $webkulHelper,
        array $data = []
    )
    {
        $this->balancepayHelper = $balancepayHelper;
        $this->webkulHelper = $webkulHelper;
        parent::__construct($context, $data);
    }

    /**
     * GetCustomerSessionId
     *
     * @return mixed
     */
    public function getCustomerSessionId()
    {
        return $this->balancepayHelper->getCustomerSessionId();
    }

    /**
     * CanShowIf
     *
     * @return bool
     */
    public function canShowIf()
    {
        try {
            $seller = ($this->webkulHelper->isSeller());
        } catch (\Exception $e) {
            $seller = false;
        }
        if (
            $this->_scopeConfig->isSetFlag('payment/balancepay/active') &&
            $this->_scopeConfig->isSetFlag('payment/balancepay/payments_terms') &&
            $this->getCustomerSessionId() &&
            !($seller)
        ) {
            return true;
        }
        return false;
    }
}
