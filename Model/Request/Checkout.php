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

use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
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
     * AbstractGateway constructor.
     *
     * @param Config                $config
     * @param Curl                  $curl
     * @param ResponseFactory       $responseFactory
     * @param CheckoutSession       $checkoutSession
     * @param CartTotalRepository   $cartTotalRepository
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        CheckoutSession $checkoutSession,
        CartTotalRepository $cartTotalRepository
    ) {
        parent::__construct(
            $balancepayConfig,
            $curl,
            $responseFactory
        );

        $this->_checkoutSession = $checkoutSession;
        $this->_cartTotalRepository = $cartTotalRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::CHECKOUT_REQUEST_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::CHECKOUT_RESPONSE_HANDLER;
    }

    /**
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
            $balanceVendorId = $quoteItem->getProduct()->getData('balancepay_vendor_id');
            if ($quoteItem->getProductType() === 'configurable' && $quoteItem->getHasChildren()) {
                foreach ($quoteItem->getChildren() as $child) {
                    $variationId = $child->getProductId();
                    $child->getProduct()->load($child->getProductId());
                    if (!$balanceVendorId) {
                        $balanceVendorId = $child->getProduct()->getData('balancepay_vendor_id');
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
