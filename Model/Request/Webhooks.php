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

use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;

/**
 * Balancepay webhooks request model.
 */
class Webhooks extends AbstractRequest
{
    /**
     * @var string
     */
    protected $_topic;

    /**
     * SetTopic
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
     * GetTopic
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->_topic;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return RequestFactory::WEBHOOKS_REQUEST_METHOD;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::WEBHOOKS_RESPONSE_HANDLER;
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
              'topic' => $this->_topic,
              'address' => $this->_balancepayConfig->getCurrentStore()->getBaseUrl() .
                  'balancepay/webhook_' . $this->_topic,
            ]
        );
    }
}
