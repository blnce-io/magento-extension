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

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\PaymentException;
use Magento\Quote\Model\Quote;
use Balancepay\Balancepay\Helper\Data as HelperData;

/**
 * Balancepay abstract request model.
 */
abstract class AbstractRequest extends AbstractApi implements RequestInterface
{
    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var ResponseInterface
     */
    protected $_responseFactory;

    /**
     * @var string|null
     */
    protected $_fallbackEmail;

    /**
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param HelperData $helper
     * @param AccountManagementInterface $accountManagement
     * @param RegionFactory $region
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        HelperData $helper,
        AccountManagementInterface $accountManagement,
        RegionFactory $region
    ) {
        parent::__construct(
            $balancepayConfig
        );

        $this->_curl = $curl;
        $this->_responseFactory = $responseFactory;
        $this->helper = $helper;
        $this->accountManagement = $accountManagement;
        $this->region = $region;
    }

    /**
     * Process Request
     *
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $this->sendRequest();

        return $this
            ->getResponseHandler()
            ->process();
    }

    /**
     * Return full endpoint to particular method for request call.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return $this->_balancepayConfig->getBalanceApiUrl() . $this->getRequestMethod();
    }

    /**
     * Return method for request call.
     *
     * @return string
     */
    abstract protected function getRequestMethod();

    /**
     * Return response handler type.
     *
     * @return string
     */
    abstract protected function getResponseHandlerType();

    /**
     * Get Parameters
     *
     * @return array
     * @throws PaymentException
     */
    protected function getParams()
    {
        return [];
    }

    /**
     * Set Fallback Email
     *
     * @method setFallbackEmail
     * @param string|null $email
     * @return AbstractRequest
     */
    public function setFallbackEmail($email = null)
    {
        $this->_fallbackEmail = $email;
        return $this;
    }

    /**
     * Get Fallback email
     *
     * @method getFallbackEmail
     * @param string|null $email
     */
    public function getFallbackEmail()
    {
        return $this->_fallbackEmail;
    }

    /**
     * Get Curl Method
     *
     * @return string
     * @throws PaymentException
     */
    protected function getCurlMethod()
    {
        return 'post';
    }

    /**
     * Send Request
     *
     * @return AbstractRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function sendRequest()
    {
        $endpoint = $this->getEndpoint();
        $params = $this->getParams();
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->_balancepayConfig->getApiKey(),
        ];
        $this->_curl->setHeaders($headers);

        $this->_balancepayConfig->log('AbstractRequest::sendRequest() ', 'debug', [
            'method' => $this->getRequestMethod(),
            'request' => [
                'Type' => $this->getCurlMethod(),
                'Endpoint' => $endpoint,
                'Headers' => $headers,
                'Params' => $params
            ],
        ]);

        $this->_curl->{$this->getCurlMethod()}($endpoint, $params);

        return $this;
    }

    /**
     * Return proper response handler.
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getResponseHandler()
    {
        $responseHandler = $this->_responseFactory->create(
            $this->getResponseHandlerType(),
            $this->_curl
        );

        return $responseHandler;
    }

    /**
     * Get Lines Parameters
     *
     * @param Quote $quote
     * @param int|float $totalShippingAmount
     * @return array
     */
    protected function getLinesParams(Quote $quote, $totalShippingAmount = 0)
    {
        $params = [];
        $totalItemsQty = 0;

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
                        $balanceVendorId = $this->helper->getBalanceVendors($variationId);
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
                'tax' => $quoteItem->getBaseTaxAmount(),
                'vendorId' => $balanceVendorId,
            ];

            $totalItemsQty += $lineItem['quantity'];
            $params[] = $lineItem;
        }

        $itemShippingPrice = $totalItemsQty ? ($totalShippingAmount / $totalItemsQty) : 0;

        $lines = [];

        foreach ($params as $param) {
            $balanceVendorId = $param['vendorId'] ?: 0;
            unset($param['vendorId']);
            if (!isset($lines[$balanceVendorId])) {
                $lines[$balanceVendorId] = [
                    "shippingPrice" => $itemShippingPrice * $lineItem['quantity'],
                    "lineItems" => [$param],
                ];
                if ($balanceVendorId) {
                    $lines[$balanceVendorId]['vendorId'] = $balanceVendorId;
                }
            } else {
                $lines[$balanceVendorId]['shippingPrice'] += $itemShippingPrice * $lineItem['quantity'];
                $lines[$balanceVendorId]['lineItems'][] = $param;
            }
        }

        foreach ($lines as &$line) {
            $line['shippingPrice'] = $this->amountFormat($line['shippingPrice']);
        }

        return array_values($lines);
    }

    /**
     * Get Billing address parameters
     *
     * @param Quote $quote
     * @return array
     */
    protected function getBillingAddressParams(Quote $quote)
    {
        $params = [];

        if (($billing = $quote->getBillingAddress()) !== null) {
            $regionName = $billing->getRegion();
            $address = (array)$billing->getStreet();
            if (empty($address[0])) {
                $billing = $this->accountManagement->getDefaultBillingAddress($quote->getCustomerId());
                $address = (array)$billing->getStreet();
                $region = $this->region->create()->load($billing->getRegionId());
                $regionName = $region->getName() ?? '';
            }
            $params = [
                'firstName' => $billing->getFirstname(),
                'lastName' => $billing->getLastname(),
                'addressLine1' => (string)array_shift($address),
                'addressLine2' => (string)implode(' ', $address),
                'zipCode' => $billing->getPostcode(),
                'countryCode' => $billing->getCountryId(),
                'state' => (string)$regionName,
                'city' => $billing->getCity(),
            ];
        }

        return $params;
    }

    /**
     * Get Shipping Address parameters
     *
     * @param Quote $quote
     * @return array
     */
    protected function getShippingAddressParams(Quote $quote)
    {
        $params = [];
        if (($shipping = $quote->getShippingAddress())) {
            $regionName = $shipping->getRegion();
            $address = (array)$shipping->getStreet();
            if (empty($address[0])) {
                $shipping = $this->accountManagement->getDefaultShippingAddress($quote->getCustomerId());
                $address = (array)$shipping->getStreet();
                $region = $this->region->create()->load($shipping->getRegionId());
                $regionName = $region->getName() ?? '';
            }
            $params = [
                'firstName' => $shipping->getFirstname(),
                'lastName' => $shipping->getLastname(),
                'addressLine1' => (string)array_shift($address),
                'addressLine2' => (string)implode(' ', $address),
                'zipCode' => $shipping->getPostcode(),
                'countryCode' => $shipping->getCountryId(),
                'state' => (string)$regionName,
                'city' => $shipping->getCity(),
            ];
        }

        return $params;
    }

    /**
     * Get Shipping lines parameters
     *
     * @param Quote $quote
     * @return array
     */
    protected function getShippingLinesParams(Quote $quote)
    {
        $params = [];

        if (($shipping = $quote->getShippingAddress()) &&
            ($rate = $shipping->getShippingRatesCollection()->getFirstItem())
        ) {
            $params = [
                'title' => $shipping->getShippingDescription(),
                'carrierIdentifier' => $rate->getCarrier(),
                'deliveryCategory' => $rate->getCarrierTitle(),
                'comments' => '-',
                'price' => $this->amountFormat($rate->getPrice()),
            ];
        }

        return $params;
    }
}
