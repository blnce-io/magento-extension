<?php
/**
 * Balance Payments For Magento 2
 * https://www.getbalance.com/
 *
 * @category Balance
 * @package  Balancepay_Balancepay
 * @author   Developer: Pniel Cohen
 * @author   Company: Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Balancepay\Balancepay\Model\Request;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Cart\CartTotalRepository;
use Magento\Quote\Model\Quote;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Balancepay\Balancepay\Model\BalanceBuyer;

/**
 * Balancepay transactions request model.
 */
class Transactions extends AbstractRequest
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CartTotalRepository
     */
    protected $_cartTotalRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var string
     */
    protected $_topic;

    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @var BalanceBuyer
     */
    private $balanceBuyer;

    /**
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param CheckoutSession $checkoutSession
     * @param CartTotalRepository $cartTotalRepository
     * @param HelperData $helper
     * @param AccountManagementInterface $accountManagement
     * @param RegionFactory $region
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param BalanceBuyer $balanceBuyer
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        CheckoutSession $checkoutSession,
        CartTotalRepository $cartTotalRepository,
        HelperData $helper,
        AccountManagementInterface $accountManagement,
        RegionFactory $region,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        BalanceBuyer $balanceBuyer
    ) {
        parent::__construct(
            $balancepayConfig,
            $curl,
            $helper,
            $responseFactory,
            $accountManagement,
            $region
        );
        $this->_checkoutSession = $checkoutSession;
        $this->_cartTotalRepository = $cartTotalRepository;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->balanceBuyer = $balanceBuyer;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::TRANSACTIONS_RESPONSE_HANDLER;
    }

    /**
     * Set topic
     *
     * @param string $topic
     * @return $this
     */
    public function setTopic($topic)
    {
        $this->_topic = (string)$topic;
        return $this;
    }

    /**
     * GetCurlMethod
     *
     * @return string
     * @throws PaymentException
     */
    protected function getCurlMethod()
    {
        if ($this->_topic == 'gettransactionid') {
            return 'get';
        }
        return 'post';
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set curl method
     *
     * @param string $requestMethod
     * @return mixed|string
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
        return $this;
    }

    /**
     * Get topic
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->_topic;
    }

    /**
     * Return request params.
     *
     * @return array
     */
    protected function getParams()
    {
        if ($this->_topic == 'gettransactionid') {
            return array_replace_recursive(
                parent::getParams(),
                []
            );
        }
        $quote = $this->_checkoutSession->getQuote();
        $quote->collectTotals();
        $requiresShipping = $quote->getShippingAddress() !== null ? 1 : 0;
        $quoteTotals = $this->_cartTotalRepository->get($quote->getId());

        $customerId = $this->customerSession->getCustomer()->getId();
        $termsOptions = $this->_balancepayConfig->getMerchantTermsOptions();
        $options = [];

        if ($customerId) {
            $customerTermsOptions = $this->getCustomerTermsOptions($customerId);
            $termsOptions = !empty($customerTermsOptions) ? $customerTermsOptions : $termsOptions;
        }

        foreach ($termsOptions as $terms) {
            $options[$terms] = $terms;
        }

        return array_replace_recursive(
            parent::getParams(),
            [
                'currency' => $quote->getBaseCurrencyCode(),
                'externalReferenceId' => $this->_balancepayConfig->getReservedOrderId($quote),
                'notes' => $this->_balancepayConfig->getReservedOrderId($quote),
                'buyer' => $this->getBuyerParams($quote),
                "plan" => [
                    "planType" => "invoice",
                    "chargeDate" => date('Y-m-d', strtotime($this->_balancepayConfig->getGmtDate())),
                ],
                "financingConfig" => !empty($options) ? ["financingNetDaysOptions" => array_keys($options),] : [],
                'lines' => $this->getLinesParams($quote, $quoteTotals->getBaseShippingAmount()),
                'shippingLines' => $this->getShippingLinesParams($quote),
                'totalDiscount' => abs($this->amountFormat($quoteTotals->getBaseDiscountAmount())),
                'billingAddress' => $this->getBillingAddressParams($quote),
                'shippingAddress' => $this->getShippingAddressParams($quote),
                'allowedPaymentMethods' => $this->_balancepayConfig->getAllowedPaymentMethods(),
                'allowedTermsPaymentMethods' => $this->_balancepayConfig->getAllowedTermsPaymentMethods(),
            ]
        );
    }

    /**
     * GetBuyerParams
     *
     * @param Quote $quote
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getBuyerParams(Quote $quote)
    {
        $params = [];
        $customerBuyerId = $this->balanceBuyer->getCustomerBalanceBuyerId();
        $isLoggedIn = $this->customerSession->isLoggedIn();

        if (($billing = $quote->getBillingAddress()) !== null && $billing->getEmail()) {
            $email = $billing->getEmail();
        } else {
            $email = $quote->getCustomerEmail() ?: $this->getFallbackEmail();
        }
        if ($isLoggedIn && $customerBuyerId != null) {
            $params['id'] = $customerBuyerId;
        } else {
            $params['email'] = $email;
            $params['isRegistered'] = false;
        }
        return $params;
    }

    /**
     * GetCustomerTermsOptions
     *
     * @param int $customerId
     * @return array|string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerTermsOptions($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $customerAttributeData = $customer->__toArray();
        $optionValues = isset($customerAttributeData['custom_attributes']['term_options']) ?
            $customerAttributeData['custom_attributes']['term_options']['value'] : '';
        if ($optionValues) {
            return explode(",", $optionValues);
        }
        return [];
    }
}
