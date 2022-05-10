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
    )
    {
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
     * GetBuyerFromTransaction
     *
     * @param mixed $transactionId
     */
    public function getBuyerFromTransaction($transactionId)
    {
        try {
            $response = $this->requestFactory
                ->create(RequestFactory::TRANSACTIONS_REQUEST_METHOD)
                ->setRequestMethod('transactions/' . $transactionId)
                ->setTopic('gettransactionid')
                ->process();
            if (!empty($response->getBuyerId())) {
                $this->updateCustomerBalanceBuyerId($response->getBuyerId());
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Could not attach buyer id to the customer', 'debug', [
                'ExceptionMessage' => $e->getMessage(),
                'TraceAsString' => $e->getTraceAsString()
            ]);
        }
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

    /**
     * Update Buyer Id
     *
     * @param mixed $buyerId
     */
    public function updateBalanceBuyerId($buyerId, $customerId)
    {
        $customer = $this->customer->load($customerId);
        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute('buyer_id', $buyerId);
        $customer->updateData($customerData);
        $customerResource = $this->customerFactory->create();
        $customerResource->saveAttribute($customer, 'buyer_id');
    }

    /**
     * GetBalanceBuyerId
     *
     * @return mixed|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBalanceBuyerId($customerId)
    {
        if ($customerId) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $customerAttributeData = $customer->__toArray();
            return isset($customerAttributeData['custom_attributes']['buyer_id']) ?
                $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
        }
        return null;
    }


}
