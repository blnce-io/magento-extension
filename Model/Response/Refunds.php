<?php
namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Model\AbstractResponse;
use Magento\Framework\Exception\PaymentException;

/**
 * Balancepay webhooks response model.
 */
class Refunds extends AbstractResponse
{
    /**
     * Process
     *
     * @return array|Refunds
     * @throws PaymentException
     */
    public function process()
    {
        parent::process();
        $body = $this->getBody();
        return $body;
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
}
