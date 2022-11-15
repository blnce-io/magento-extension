<?php

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
 * Balancepay cancel request model.
 */
class Cancel extends AbstractRequest
{
    /**
     * @var OrderPayment
     */
    protected $_payment;

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
            $balancepayConfig,
            $curl,
            $helper,
            $responseFactory,
            $accountManagement,
            $region
        );
    }

    /**
     * Set Payment
     *
     * @method setPayment
     * @param  OrderPayment $payment
     * @return Cancel $this
     */
    public function setPayment(OrderPayment $payment)
    {
        $this->_payment = $payment;
        return $this;
    }

    /**
     * Get Payment
     *
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
     * @inheritdoc
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::TRANSACTION_CANCEL_REQUEST_METHOD;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::TRANSACTION_CANCEL_RESPONSE_METHOD;
    }
}
