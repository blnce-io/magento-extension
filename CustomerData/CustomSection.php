<?php
namespace Balancepay\Balancepay\CustomerData;

use Balancepay\Balancepay\Helper\Data as BalancepayHelper;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\Helper\Data;

/**
 * Class CustomSection
 * @package Balancepay\Balancepay\CustomerData
 */
class CustomSection implements SectionSourceInterface
{
    /**
     * @var Data
     */
    protected $pricingHelper;

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
     * @var BalancepayHelper
     */
    protected $balancepayHelper;

    /**
     * @param Data $pricingHelper
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param RequestFactory $requestFactory
     * @param BalancepayConfig $balancepayConfig
     * @param BalancepayHelper $balancepayHelper
     */
    public function __construct(
        Data $pricingHelper,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        RequestFactory $requestFactory,
        BalancepayConfig $balancepayConfig,
        BalancepayHelper $balancepayHelper
    ) {
        $this->customerSession = $customerSession;
        $this->pricingHelper = $pricingHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->requestFactory = $requestFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->balancepayHelper = $balancepayHelper;
    }

    /**
     * @return string[]
     */
    public function getSectionData()
    {
        $status = false;
        $showButton = true;
        $showCredit = false;
        $creditLimit = '$0.00';
        $buyerResponse = $this->getBuyerAmount();
        if (!empty($buyerResponse['qualificationStatus']) && $buyerResponse['qualificationStatus'] == 'completed'){
            $status = true;
            $showButton = false;
            $showCredit = true;
            $creditLimit =  $this->formattedAmount($buyerResponse['qualification']['creditLimit'] ?? 0);
        }
        return [
            'creditLimit' => 'Terms Limit: '.$creditLimit,
            'status' => $status, // $buyerResponse['qualificationStatus'] ?? ''
            'showButton' => $showButton,
            'showCredit' => $showCredit
        ];
    }

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
     * GetCustomerSessionBuyerId
     *
     * @return mixed
     */
    public function getCustomerSessionBuyerId()
    {
        if ($this->customerSession->getBuyerId()) {
            return $this->customerSession->getBuyerId();
        }
        $customerId = $this->balancepayHelper->getCustomerSessionId();
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
     * @param $price
     * @return float|string
     */
    public function formattedAmount($price)
    {
        return $this->pricingHelper->currency($price/100,true,false);
    }
}
