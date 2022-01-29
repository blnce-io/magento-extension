<?php
namespace Balancepay\Balancepay\Block;

use Magento\Backend\Block\Template;
use \Magento\Customer\Model\Session;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Html\Link;
use Balancepay\Balancepay\Helper\Data as BalancepayHelper;

/**
 * Class Createbuyer
 *
 * Balancepay\Balancepay\Block
 */
class Createbuyer extends Link
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
     * @param Template\Context $context
     * @param BalancepayHelper $balancepayHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        BalancepayHelper $balancepayHelper,
        array $data = []
    ) {
        $this->balancepayHelper = $balancepayHelper;
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
}
