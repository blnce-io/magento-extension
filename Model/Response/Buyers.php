<?php
namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Model\AbstractResponse;

/**
 * Balancepay webhooks response model.
 */
class Buyers extends AbstractResponse
{

    /**
     * @var string
     */
    protected $_token;

    /**
     * Process
     *
     * @return array|Buyers
     * @throws \Magento\Framework\Exception\PaymentException
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
