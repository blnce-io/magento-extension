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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Quote\Model\Cart\CartTotalRepository;
use Magento\Quote\Model\Quote;

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
    protected $customerRepository;

    /**
     * @var Session
     */
    protected $customerSession;

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
        Session $customerSession
    ) {
        parent::__construct(
            $balancepayConfig,
            $curl,
            $responseFactory,
            $helper,
            $accountManagement,
            $region
        );

        $this->_checkoutSession = $checkoutSession;
        $this->_cartTotalRepository = $cartTotalRepository;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::TRANSACTIONS_REQUEST_METHOD;
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
     * Return request params.
     *
     * @return array
     */
    protected function getParams()
    {
        $quote = $this->_checkoutSession->getQuote();
        $quote->collectTotals();
        $requiresShipping = $quote->getShippingAddress() !== null ? 1 : 0;
        $quoteTotals = $this->_cartTotalRepository->get($quote->getId());
        $termsOptions = $this->getTermOptions($this->customerSession->getCustomer()->getId());
        $options = [];
        if (!empty($termsOptions)) {
            foreach ($termsOptions as $terms) {
                $options[$terms] = $terms;
            }
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
                "financingConfig" => !empty($options) ? [ "financingNetDaysOptions" => array_keys($options), ] : [],
                'lines' => $this->getLinesParams($quote, $quoteTotals->getBaseShippingAmount()),
                'shippingLines' => $this->getShippingLinesParams($quote),
                'totalDiscount' => abs($this->amountFormat($quoteTotals->getBaseDiscountAmount())),
                'billingAddress' => $this->getBillingAddressParams($quote),
                'shippingAddress' => $this->getShippingAddressParams($quote),
                'allowedPaymentMethods' => $this->_balancepayConfig->getAllowedPaymentMethods(),
            ]
        );
    }

    /**
     * GetBuyerParams
     *
     * @param Quote $quote
     * @return array
     */
    protected function getBuyerParams(Quote $quote)
    {
        $params = [];

        if (($billing = $quote->getBillingAddress()) !== null && $billing->getEmail()) {
            $params['email'] = $billing->getEmail();
        } else {
            $params['email'] = $quote->getCustomerEmail() ?: $this->getFallbackEmail();
        }

        $params['isRegistered'] = $quote->getCustomerIsGuest() ? false : true;

        return $params;
    }

    /**
     * GetTermOptions
     *
     * @param int $customerId
     * @return array|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTermOptions($customerId)
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
