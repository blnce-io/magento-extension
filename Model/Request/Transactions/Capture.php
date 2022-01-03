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

namespace Balancepay\Balancepay\Model\Request\Transactions;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Balancepay transactions/capture request model.
 */
class Capture extends AbstractRequest
{
    /**
     * @var int|float|null
     */
    protected $_amount;

    /**
     * @var mixed
     */
    protected $_balanceVendorId;

    /**
     * @var OrderPayment
     */
    protected $_payment;

    /**
     * Capture constructor.
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param HelperData $helper
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
            $balancepayConfig,
            $curl,
            $responseFactory,
            $helper,
            $accountManagement,
            $region
        );
    }

    /**
     * @method setAmount
     * @param  int|float $amount
     * @return Capture $this
     */
    public function setAmount($amount)
    {
        $this->_amount = $amount;
        return $this;
    }

    /**
     * @method getAmount
     * @return int|float|null
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * @method setBalanceVendorId
     * @param  mixed $balanceVendorId
     * @return Capture $this
     */
    public function setBalanceVendorId($balanceVendorId)
    {
        $this->_balanceVendorId = $balanceVendorId;
        return $this;
    }

    /**
     * @method getBalanceVendorId
     * @return mixed
     */
    public function getBalanceVendorId()
    {
        return $this->_balanceVendorId;
    }

    /**
     * @method setPayment
     * @param  OrderPayment $payment
     * @return Capture $this
     */
    public function setPayment(OrderPayment $payment)
    {
        $this->_payment = $payment;
        return $this;
    }

    /**
     * @method getPayment
     * @return OrderPayment|null
     */
    public function getPayment()
    {
        return $this->_payment;
    }

    /**
     * Return full endpoint to particular method for request call.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return $this->_balancepayConfig->getBalanceApiUrl() .
            sprintf(
                'transactions/%s/%s',
                $this->getPayment()->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID),
                $this->getRequestMethod()
            );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::CAPTURE_REQUEST_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::CAPTURE_RESPONSE_HANDLER;
    }

    /**
     * Return request params.
     *
     * @return array
     */
    protected function getParams()
    {
        $params = [
            "captureAmount" => (float) $this->_amount
        ];
        if ($this->_balanceVendorId) {
            $params["vendorId"] = $this->_balanceVendorId;
        }
        return array_replace_recursive(
            parent::getParams(),
            $params
        );
    }
}
