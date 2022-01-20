<?php
/**
 *
 */

namespace Balancepay\Balancepay\Block;

use Magento\Backend\Block\Template;
use \Magento\Customer\Model\Session;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;

/**
 * Class Createbuyer
 *
 * Balancepay\Balancepay\Block
 */
class Createbuyer extends Template
{
    /**
     * @var BalancepayConfig
     */
    private $balancepayConfig;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * Createbuyer constructor.
     *
     * @param Template\Context $context
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param RequestFactory $requestFactory
     * @param BalancepayConfig $balancepayConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        RequestFactory $requestFactory,
        BalancepayConfig $balancepayConfig,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->requestFactory = $requestFactory;
        $this->balancepayConfig = $balancepayConfig;
        parent::__construct($context, $data);
    }

    /**
     * GetBuyerAmount
     *
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBuyerAmount($customerId)
    {
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerAttributeData = $customer->__toArray();
        $response = [];
        try {
            $buyerId = isset($customerAttributeData['custom_attributes']['buyer_id']) ?
                $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
            if (!empty($buyerId)) {
                $response = $this->requestFactory
                    ->create(RequestFactory::BUYER_REQUEST_METHOD)
                    ->setRequestMethod('buyers/' . $buyerId)
                    ->setTopic('getbuyers')
                    ->process();
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Webhook\Checkout\Charged::execute() [Exception: ' .
                $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
        }
        return $response;
    }

    /**
     * GetCustomerSessionId
     *
     * @return mixed
     */
    public function getCustomerSessionId()
    {
        return $this->customerSession->getCustomer()->getId();
    }

    /**
     * GetCustomerSessionBuyerId
     *
     * @return mixed
     */
    public function getCustomerSessionBuyerId()
    {
        return $this->customerSession->getCustomer()->getBuyerId();
    }
}
