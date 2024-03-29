<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Balancepay\Balancepay\Model\Config;

class BalanceBuyer
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var HelperData
     */
    private $helper;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * BalanceBuyer constructor.
     *
     * @param RequestFactory $requestFactory
     * @param HelperData $helper
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param LoggerInterface $logger
     * @param Config $balancepayConfig
     */
    public function __construct(
        RequestFactory $requestFactory,
        HelperData $helper,
        Customer $customer,
        CustomerFactory $customerFactory,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        LoggerInterface $logger,
        Config $balancepayConfig
    ) {
        $this->requestFactory = $requestFactory;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->logger = $logger;
        $this->balancepayConfig = $balancepayConfig;
    }

    /**
     * UpdateCustomerBalanceBuyerId
     *
     * @param int|string|mixed|null $buyerId
     * @param int $customerId
     * @throws \Exception
     */
    public function updateCustomerBalanceBuyerId($buyerId, $customerId = 0)
    {
        if (!$customerId) {
            $customerId = $this->customerSession->getCustomer()->getId();
        }
        $customer = $this->customer->load($customerId);
        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute('buyer_id', $buyerId);
        $customer->updateData($customerData);
        $customerResource = $this->customerFactory->create();
        $customerResource->saveAttribute($customer, 'buyer_id');
    }

    /**
     * GetCustomerBalanceBuyerId
     *
     * @return mixed|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerBalanceBuyerId()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        if ($customerId) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $customerAttributeData = $customer->__toArray();
            return isset($customerAttributeData['custom_attributes']['buyer_id']) ?
                $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
        }
        return null;
    }
}
