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
use Magento\Quote\Model\Cart\CartTotalRepository;
use Magento\Quote\Model\Quote;

/**
 * Balancepay checkout request model.
 */
class Checkout extends AbstractRequest
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
     * @var HelperData
     */
    protected $helper;

    /**
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param CheckoutSession $checkoutSession
     * @param CartTotalRepository $cartTotalRepository
     * @param HelperData $helper
     * @param AccountManagementInterface $accountManagement
     * @param RegionFactory $region
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        CheckoutSession $checkoutSession,
        CartTotalRepository $cartTotalRepository,
        HelperData $helper,
        AccountManagementInterface $accountManagement,
        RegionFactory $region
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
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::CHECKOUT_REQUEST_METHOD;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::CHECKOUT_RESPONSE_HANDLER;
    }

    /**
     * Amount format
     *
     * @method amountFormat
     * @param  float|int              $amount
     * @return string
     */
    protected function amountFormat($amount)
    {
        return (string) number_format((float)$amount, 2, '.', '');
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

        return array_replace_recursive(
            parent::getParams(),
            [
              'currency' => $quote->getBaseCurrencyCode(),
              'cartToken' => $this->_balancepayConfig->getReservedOrderId($quote),
              'buyer' => $this->getBuyerParams($quote),
              'transactions' => [[
                  'seller' => '',
                  'totalLineItems' => $this->amountFormat($quoteTotals->getBaseSubtotal()),
                  'shippingPrice' => $this->amountFormat($quoteTotals->getBaseShippingAmount()),
                  'tax' => $this->amountFormat($quoteTotals->getBaseTaxAmount()),
                  'lineItems' => $this->getLineItemsParams($quote),
              ]],
              'shippingLines' => $this->getShippingLinesParams($quote),
              'totalLineItems' => $this->amountFormat($quoteTotals->getBaseSubtotal()),
              'totalTax' => $this->amountFormat($quoteTotals->getBaseTaxAmount()),
              'totalPrice' => $this->amountFormat($quoteTotals->getBaseGrandTotal()),
              'totalShipping' => $this->amountFormat($quoteTotals->getBaseShippingAmount()),
              'totalDiscount' => abs($this->amountFormat($quoteTotals->getBaseDiscountAmount())),
              'billingAddress' => $this->getBillingAddressParams($quote),
              'requiresShipping' => $requiresShipping ? true : false,
              'allowedPaymentMethods' => $this->_balancepayConfig->getAllowedPaymentMethods(),
            ]
        );
    }

    /**
     * Get Buyer params
     *
     * @param Quote $quote
     *
     * @return array
     */
    protected function getBuyerParams(Quote $quote)
    {
        $params = [];

        if (($billing = $quote->getBillingAddress()) !== null) {
            $params = [
                'email' => $billing->getEmail() ?: ($quote->getCustomerEmail() ?: $this->getFallbackEmail()),
                'firstName' => $billing->getFirstname(),
                'lastName' => $billing->getLastname(),
                'businessName' => $billing->getCompany(),
                'phone' => $billing->getTelephone(),
            ];
        }

        return $params;
    }

    /**
     * Get billing address params
     *
     * @param Quote $quote
     *
     * @return array
     */
    protected function getBillingAddressParams(Quote $quote)
    {
        $params = [];

        if (($billing = $quote->getBillingAddress()) !== null) {
            $params = [
              'streetAddress1' => is_array($billing->getStreet()) ? implode(' ', $billing->getStreet()) : '',
              'countryCode' => $billing->getCountryId(),
              'state' => (string) $billing->getRegion(),
              'city' => $billing->getCity(),
              'zipCode' => $billing->getPostcode(),
            ];
        }

        return $params;
    }

    /**
     * Get line items params
     *
     * @param Quote $quote
     *
     * @return array
     */
    protected function getLineItemsParams(Quote $quote)
    {
        $params = [];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $price = $this->amountFormat($quoteItem->getBasePrice());
            if (!$price) {
                continue;
            }
            $variationId = $quoteItem->getProductId();
            $quoteItem->getProduct()->load($quoteItem->getProductId());
            $balanceVendorId = $this->helper->getBalanceVendors($variationId);
            if ($quoteItem->getProductType() === 'configurable' && $quoteItem->getHasChildren()) {
                foreach ($quoteItem->getChildren() as $child) {
                    $variationId = $child->getProductId();
                    $child->getProduct()->load($child->getProductId());
                    if (!$balanceVendorId) {
                        $balanceVendorId = $this->helper->getBalanceVendors($child->getProductId());
                    }
                    continue;
                }
            }

            $lineItem = [
                'title' => $quoteItem->getName(),
                'quantity' => (int)$quoteItem->getQty(),
                'productId' => $quoteItem->getProductId(),
                'productSku' => $quoteItem->getSku(),
                'variationId' => $variationId,
                'itemType' => $quoteItem->getIsVirtual() ? 'VIRTUAL' : 'PHYSICAL',
                'price' => $price,
            ];

            if ($balanceVendorId) {
                $lineItem['vendorId'] = $balanceVendorId;
            }

            $params[] = $lineItem;
        }

        return $params;
    }
}
