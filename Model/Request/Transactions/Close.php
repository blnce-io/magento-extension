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
use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Balancepay close request model.
 */
class Close extends AbstractRequest
{
    /**
     * @var OrderPayment
     */
    protected $_payment;

    /**
     * Close constructor.
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param HelperData $helper
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        HelperData $helper
    ) {
        parent::__construct(
            $balancepayConfig,
            $curl,
            $responseFactory,
            $helper
        );
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
        return RequestFactory::CLOSE_REQUEST_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::CLOSE_RESPONSE_HANDLER;
    }

    /**
     * Return request params.
     *
     * @return array
     */
    protected function getParams()
    {
        return parent::getParams();
    }
}
