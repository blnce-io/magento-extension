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

/**
 * Balancepay webhooks request model.
 */
class Vendors extends AbstractRequest
{
    /**
     * Vendors constructor.
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param ResponseFactory $responseFactory
     * @param HelperData $helper
     * @param AccountManagementInterface $accountManagement
     * @param RegionFactory $region
     * @param RequestInterface $request
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        HelperData $helper,
        AccountManagementInterface $accountManagement,
        RegionFactory $region,
        RequestInterface $request
    ) {
        $this->request = $request;
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
     * @var string
     */
    protected $_topic;

    /**
     * @var string
     */
    protected $requestMethod;

    /**
     * Set topic
     *
     * @param  string   $topic
     * @return $this
     */
    public function setTopic($topic)
    {
        $this->_topic = (string) $topic;
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
     * Get Curl method
     *
     * @return string
     * @throws PaymentException
     */
    protected function getCurlMethod()
    {

        if ($this->_topic == 'create-vendors') {
            return 'post';
        }
        return 'get';
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
     * Get Response Handler type
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::VENDORS_RESPONSE_HANDLER;
    }

    /**
     * Get parameters
     *
     * @return array
     * @throws \Magento\Framework\Exception\PaymentException
     */
    protected function getParams()
    {
        $requestParams = $this->request->getParams();
        $params = [
            'topic' => $this->_topic
        ];

        if ($this->_topic == 'create-vendors') {
            if (isset($requestParams['firstname'])) {
                $params['name'] = $requestParams['firstname'] ?? '' . ' ' . $requestParams['lastname'] ?? '';
            } elseif (isset($requestParams['customer'])) {
                $params['name'] = $requestParams['customer']['firstname']. ' ' . $requestParams['customer']['lastname'];
            }
            if (isset($requestParams['email'])) {
                $params['emailAddress'] = $requestParams['email'] ?? '';
            } elseif (isset($requestParams['customer'])) {
                $params['emailAddress'] = $requestParams['customer']['email'] ?? '';
            }
            $params['businessDomain'] = (isset($requestParams['profileurl'])) ? $requestParams['profileurl'] : '';
            $params['url'] = (isset($requestParams['profileurl'])) ? $requestParams['profileurl'] : '';
        }

        return array_replace_recursive(
            parent::getParams(),
            $params
        );
    }
}
