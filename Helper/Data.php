<?php

namespace Balancepay\Balancepay\Helper;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Balancepay\Balancepay\Model\WebhookFactory;
use Magento\Customer\Model\ResourceModel\CustomerFactory;

class Data extends AbstractHelper
{
    /**
     * @var MpProductCollection
     */
    protected $_mpProductCollectionFactory;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var Context
     */
    protected $appContext;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var BalancepayConfig
     */
    protected $balancepayConfig;

    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @var string[]
     */
    public $ccIcons;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Data constructor.
     *
     * @param MpProductCollection $mpProductCollectionFactory
     * @param Context $appContext
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param RequestFactory $requestFactory
     * @param BalancepayConfig $balancepayConfig
     * @param PricingHelper $pricingHelper
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        MpProductCollection $mpProductCollectionFactory,
        Context $appContext,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        RequestFactory $requestFactory,
        BalancepayConfig $balancepayConfig,
        PricingHelper $pricingHelper,
        Customer $customer,
        CustomerFactory $customerFactory
    ) {
        $this->ccIcons = [
            'visa' => 'vi',
            'discover' => 'di',
            'mastercard' => 'mc',
            'maestro' => 'mi'
        ];
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->appContext = $appContext;
        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->requestFactory = $requestFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->pricingHelper = $pricingHelper;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Get balance Vendors
     *
     * @param string $productId
     * @return string
     */
    public function getBalanceVendors($productId = '')
    {
        return $this->getSellerIdByProductId($productId);
    }

    /**
     * Return the seller Id by product id.
     *
     * @param string $productId
     * @return mixed
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $sellerId = $collection->getFirstItem()->getVendorId();
        return $sellerId;
    }

    /**
     * GetCustomerSessionId
     *
     * @return mixed
     */
    public function getCustomerSessionId()
    {
        return $this->appContext->getValue('customer_id');
    }

    /**
     * GetBuyerAmount
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBuyerAmount()
    {
        $response = [];
        try {
            $buyerId = $this->getCustomerSessionBuyerId();
            if (!empty($buyerId)) {
                $response = $this->requestFactory
                    ->create(RequestFactory::BUYER_REQUEST_METHOD)
                    ->setRequestMethod('buyers/' . $buyerId)
                    ->setTopic('getbuyers')
                    ->process();
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Get Buyer [Exception: ' .
                $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
        }
        return $response;
    }

    /**
     * Update Buyer Id
     *
     * @param $buyerId
     */
    public function updateBuyerId($buyerId)
    {
        $customer = $this->customer->load($this->customerSession->getCustomer()->getId());
        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute('buyer_id', $buyerId);
        $customer->updateData($customerData);
        $customerResource = $this->customerFactory->create();
        $customerResource->saveAttribute($customer, 'buyer_id');
    }

    /**
     * GetBuyerId
     *
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBuyerId()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerAttributeData = $customer->__toArray();
        return isset($customerAttributeData['custom_attributes']['buyer_id']) ?
            $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
    }

    /**
     * GetCustomerSessionBuyerId
     *
     * @return mixed
     */
    public function getCustomerSessionBuyerId()
    {
        if ($this->customerSession->getBuyerId()) {
            return $this->customerSession->getBuyerId();
        }
        $customerId = $this->getCustomerSessionId();
        if (!empty($customerId)) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $customerAttributeData = $customer->__toArray();
            $buyerId = isset($customerAttributeData['custom_attributes']['buyer_id']) ?
                $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
            $this->customerSession->setBuyerId($buyerId);
            return $buyerId;
        }
        return 0;
    }

    /**
     * FormattedAmount
     *
     * @param float|string $price
     * @return float|string
     */
    public function formattedAmount($price)
    {
        return $this->pricingHelper->currency($price / 100, true, false);
    }

    /**
     * IsCustomerGroupAllowed
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCustomerGroupAllowed()
    {
        $currentCustomerGroup = $this->customerSession->getCustomer()->getGroupId();
        $allowedCustomerGroups = $this->balancepayConfig->getAllowedCustomerGroups();
        return in_array($currentCustomerGroup, $allowedCustomerGroups);
    }
}
