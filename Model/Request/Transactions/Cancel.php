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
use Magento\Framework\Exception\PaymentException;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Balancepay cancel request model.
 */
class Cancel extends AbstractRequest
{
    /**
     * @var transactionId
     */
    private $transactionId;

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
     * SetTransactionId
     *
     * @param int|string|null|mixed $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * GetTransactionId
     *
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transactionId;
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
                $this->getTransactionId(),
                $this->getRequestMethod()
            );
    }

    /**
     * Get Curl method
     *
     * @return string
     * @throws PaymentException
     */
    protected function getCurlMethod()
    {
        return 'post';
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
