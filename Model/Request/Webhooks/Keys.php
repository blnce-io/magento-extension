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

namespace Balancepay\Balancepay\Model\Request\Webhooks;

use Balancepay\Balancepay\Model\AbstractRequest;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;

/**
 * Balancepay webhooks/keys request model.
 */
class Keys extends AbstractRequest
{
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
    protected function getRequestMethod()
    {
        return RequestFactory::WEBHOOKS_KEYS_REQUEST_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return ResponseFactory::WEBHOOKS_KEYS_RESPONSE_HANDLER;
    }
}
