<?php
namespace Balancepay\Balancepay\Model\Request;

use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;

/**
 * Balancepay webhooks request model.
 */
class Vendors extends AbstractRequest
{
    /**
     * @var string
     */
    protected $_topic;

    /**
     * @param  string   $topic
     *
     * @return $this
     */
    public function setTopic($topic)
    {
        $this->_topic = (string) $topic;
        return $this;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->_topic;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::VENDORS_REQUEST_METHOD;
    }

    /**
     * @return string
     * @throws PaymentException
     */
    protected function getCurlMethod()
    {
        return 'get';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::VENDORS_RESPONSE_HANDLER;
    }

    /**
     * Return request params.
     *
     * @return array
     */
    protected function getParams()
    {
        return array_replace_recursive(
            parent::getParams(),
            [
                'topic' => $this->_topic
            ]
        );
    }
}
