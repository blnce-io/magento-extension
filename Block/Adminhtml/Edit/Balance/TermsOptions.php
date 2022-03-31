<?php
namespace Balancepay\Balancepay\Block\Adminhtml\Edit\Balance;

use Magento\Backend\Block\Template;
use Balancepay\Balancepay\Model\Config;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * DashboardLink Class
 */
class TermsOptions extends Template
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'balance/terms_options.phtml';

    /**
     * @var Config
     */
    protected $balancepayConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * TermsOptions constructor.
     *
     * @param Template\Context $context
     * @param Config $balancepayConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $balancepayConfig,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    /**
     * IsTermsOptionSet
     *
     * @return bool
     */
    public function isTermsOptionSet()
    {
        return (bool) $this->getTermsOptionAttribute();
    }

    /**
     * GetTermsOptionAttribute
     *
     * @return false|mixed
     */
    public function getTermsOptionAttribute()
    {
        $customerId = $this->getRequest()->getParam('id');
        try {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer->getCustomAttribute('term_options')) {
                return $customer->getCustomAttribute('term_options')->getValue();
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}
