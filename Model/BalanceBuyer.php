<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Psr\Log\LoggerInterface;

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
    private LoggerInterface $logger;

    /**
     * @param RequestFactory $requestFactory
     * @param HelperData $helper
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestFactory $requestFactory,
        HelperData $helper,
        Customer $customer,
        CustomerFactory $customerFactory,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        LoggerInterface $logger
    ) {
        $this->requestFactory = $requestFactory;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->logger = $logger;
    }

    /**
     * GetBuyerFromTransaction
     *
     * @param mixed $transactionId
     */
    public function getBuyerFromTransaction($transactionId)
    {
        try {
            if ($this->customerSession->isLoggedIn() && empty($this->getCustomerBalanceBuyerId()) && $transactionId) {
                $response = $this->requestFactory
                    ->create(RequestFactory::TRANSACTIONS_REQUEST_METHOD)
                    ->setRequestMethod('transactions/' . $transactionId)
                    ->setTopic('gettransactionid')
                    ->process();
                if (!empty($response->getBuyerId())) {
                    $this->updateCustomerBalanceBuyerId($response->getBuyerId());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('There is an while fetching Buyer Id from transaction.');
        }
    }

    /**
     * Update Buyer Id
     *
     * @param mixed $buyerId
     */
    public function updateCustomerBalanceBuyerId($buyerId)
    {
        $customer = $this->customer->load($this->customerSession->getCustomer()->getId());
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerBalanceBuyerId()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerAttributeData = $customer->__toArray();
        return isset($customerAttributeData['custom_attributes']['buyer_id']) ?
            $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
    }
}
