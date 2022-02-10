<?php

namespace Balancepay\Balancepay\Helper;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use \Webkul\Marketplace\Model\SellerFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @var string[]
     */
    public $ccIcons;

    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @var SellerFactory
     */
    protected $sellerFactory;

    /**
     * @var MpProductCollection
     */
    protected $_mpProductCollectionFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var Context
     */
    protected $appContext;

    /**
     * @var BalancepayConfig
     */
    protected $balancepayConfig;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * Data constructor.
     *
     * @param SellerFactory $sellerFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param CollectionFactory $collectionFactory
     * @param PricingHelper $pricingHelper
     * @param Context $appContext
     * @param Session $customerSession
     * @param BalancepayConfig $balancepayConfig
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        SellerFactory $sellerFactory,
        MpProductCollection $mpProductCollectionFactory,
        CollectionFactory $collectionFactory,
        PricingHelper $pricingHelper,
        Context $appContext,
        Session $customerSession,
        BalancepayConfig $balancepayConfig,
        CustomerRepositoryInterface $customerRepositoryInterface,
        RequestFactory $requestFactory
    ) {
        $this->ccIcons = [
            'visa' => 'vi',
            'discover' => 'di',
            'mastercard' => 'mc',
            'maestro' => 'mi',
            'amex' => 'ae',
            'discover' => 'di',
            'jcb' => 'jcb'
        ];
        $this->sellerFactory = $sellerFactory;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->pricingHelper = $pricingHelper;
        $this->appContext = $appContext;
        $this->customerSession = $customerSession;
        $this->balancepayConfig = $balancepayConfig;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Get Vendor Id
     *
     * @param int $sellerId
     * @return string
     */
    public function getVendorId($sellerId)
    {
        $balancepay_vendor_id = '';
        $collection = $this->sellerFactory->create()
            ->getCollection()
            ->addFieldToSelect('balance_vendor_id')
            ->addFieldToFilter('is_seller', \Webkul\Marketplace\Model\Seller::STATUS_ENABLED)
            ->addFieldToFilter('seller_id', $sellerId)->getFirstItem()->getData();
        if (!empty($collection['balance_vendor_id'])) {
            return $collection['balance_vendor_id'];
        }
        return $balancepay_vendor_id;
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
     * GetBuyerDetails
     *
     * @return array
     */
    public function getBuyerDetails()
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
     * @param $price
     * @return float|string
     */
    public function formattedAmount($price)
    {
        return $this->pricingHelper->currency($price/100,true,false);
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
}
