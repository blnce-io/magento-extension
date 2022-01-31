<?php
namespace Balancepay\Balancepay\Block;

use Magento\Backend\Block\Template;
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
