<?php
namespace Balancepay\Balancepay\Block;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Element\Html\Link;
use Balancepay\Balancepay\Helper\Data as BalancepayHelper;

class CreateBuyerLink extends \Magento\Framework\View\Element\Template
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
     * CreateBuyerLink constructor.
     *
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

    /**
     * IsCustomerGroupAllowed
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCustomerGroupAllowed()
    {
        return $this->balancepayHelper->isCustomerGroupAllowed();
    }
}
