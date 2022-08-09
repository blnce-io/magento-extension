<?php

namespace Balancepay\Balancepay\Model\Request;

use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Helper\Data as HelperData;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Address;
use Magento\Framework\Exception\PaymentException;

/**
 * Balancepay webhooks request model.
 */
class Refunds extends AbstractRequest
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var mixed
     */
    private $amount;

    /**
     * @var int
     */
    private $chargeId;

    /**
     * Buyers constructor.
     *
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param HelperData $helper
     * @param AccountManagementInterface $accountManagement
     * @param RegionFactory $region
     * @param RequestInterface $request
     * @param Session $session
     * @param Address $address
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        HelperData $helper,
        AccountManagementInterface $accountManagement,
        RegionFactory $region,
        RequestInterface $request,
        Session $session,
        Address $address
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->address = $address;
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
     * @var string
     */
    protected $topic;

    /**
     * @var string
     */
    protected $requestMethod;

    /**
     * Set topic
     *
     * @param string $topic
     * @return $this
     */
    public function setTopic($topic)
    {
        $this->topic = (string)$topic;
        return $this;
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
     * Get topic
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
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
     * Get request method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set Amount
     *
     * @param mixed $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get Amount
     *
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set Charge Id
     *
     * @param int $chargeId
     * @return $this
     */
    public function setChargeId($chargeId)
    {
        $this->chargeId = $chargeId;
        return $this;
    }

    /**
     * Get Charge Id
     *
     * @return int
     */
    public function getChargeId()
    {
        return $this->chargeId;
    }

    /**
     * Set Reason
     *
     * @param string $reason
     * @return $this
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Get Reason
     *
     * @return int
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Get Response Handler type
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::BUYERS_RESPONSE_HANDLER;
    }

    /**
     * Get parameters
     *
     * @return array
     * @throws PaymentException
     */
    protected function getParams()
    {
        $params = [
            'topic' => $this->topic,
            'amount' => $this->amount,
            'chargeId' => $this->chargeId,
            'reason' => $this->reason
        ];
        return array_replace_recursive(
            parent::getParams(),
            $params
        );
    }
}
