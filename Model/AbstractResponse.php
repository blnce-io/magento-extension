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
use Magento\Framework\DataObject;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Phrase;
use Zend\Uri\Uri;

/**
 * Balancepay abstract response model.
 */
abstract class AbstractResponse extends AbstractApi implements ResponseInterface
{
    /**
     * Response result const.
     */
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILED = 2;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var int
     */
    protected $_status;

    /**
     * @var array
     */
    protected $_headers;

    /**
     * @var array
     */
    protected $_body;

    /**
     * @param Config $balancepayConfig
     * @param Curl $curl
     * @param Uri $zendUri
     */
    public function __construct(
        Config $balancepayConfig,
        Curl $curl,
        Uri $zendUri
    ) {
        parent::__construct(
            $balancepayConfig
        );
        $this->_curl = $curl;
        $this->zendUri = $zendUri;
    }

    /**
     * Process
     *
     * @return AbstractResponse
     * @throws PaymentException
     */
    public function process()
    {
        $requestStatus = $this->getRequestStatus();

        $this->_balancepayConfig->log('AbstractResponse::process() ', 'debug', [
            'response' => $this->prepareResponseData(),
            'status' => $requestStatus === true
                ? self::STATUS_SUCCESS
                : self::STATUS_FAILED,
        ]);

        if ($requestStatus === false) {
            throw new PaymentException($this->getErrorMessage());
        }

        $this->validateResponseData();

        return $this;
    }

    /**
     * GetErrorMessage
     *
     * @return Phrase
     */
    protected function getErrorMessage()
    {
        $errorReason = $this->getErrorReason();
        if ($errorReason !== false && $this->_balancepayConfig->isDebugEnabled()) {
            return __('Request to Balance payment gateway failed. Details: "%1".', $errorReason);
        }

        return __('Request to Balance payment gateway failed.');
    }

    /**
     * GetErrorReason
     *
     * @return bool
     */
    protected function getErrorReason()
    {
        $body = $this->getBody();
        if (is_array($body) && isset($body['message']) && !empty($body['message'])) {
            return implode(". \n", (array)$body['message']);
        }
        return false;
    }

    /**
     * Determine if request succeed or failed.
     *
     * @return bool
     */
    protected function getRequestStatus()
    {
        $httpStatus = $this->getStatus();
        if (!in_array($httpStatus, [100, 200, 201])) {
            return false;
        }

        return true;
    }

    /**
     * GetRequestId
     *
     * @return int
     */
    protected function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * GetStatus
     *
     * @return int
     */
    protected function getStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->_curl->getStatus();
        }

        return $this->_status;
    }

    /**
     * GetHeaders
     *
     * @return array
     */
    protected function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = $this->_curl->getHeaders();
        }

        return $this->_headers;
    }

    /**
     * GetBody
     *
     * @return array
     */
    protected function getBody()
    {
        if ($this->_body === null) {
            $body = $this->_curl->getBody();
            $this->_body = (array) json_decode($body, 1);
            if ($body && !$this->_body) {
                $this->zendUri->setQuery($body);
                $this->_body = $this->zendUri->getQueryAsArray();
            }
        }

        return $this->_body;
    }

    /**
     * PrepareResponseData
     *
     * @return array
     */
    protected function prepareResponseData()
    {
        return [
            'Status' => $this->getStatus(),
            'Headers' => $this->getHeaders(),
            'Body' => $this->getBody(),
        ];
    }

    /**
     * ValidateResponseData
     *
     * @return AbstractResponse
     * @throws PaymentException
     */
    protected function validateResponseData()
    {
        $requiredKeys = $this->getRequiredResponseDataKeys();
        $bodyKeys = array_keys($this->getBody());

        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new PaymentException(
                __(
                    'Balancepay required response data fields are missing: %1.',
                    implode(', ', $diff)
                )
            );
        }

        return $this;
    }

    /**
     * GetRequiredResponseDataKeys
     *
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return [];
    }

    /**
     * GetDataObject
     *
     * @return DataObject
     */
    public function getDataObject()
    {
        return new DataObject($this->getBody());
    }
}
