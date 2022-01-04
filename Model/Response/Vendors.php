<?php
namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Model\AbstractResponse;

/**
 * Balancepay webhooks response model.
 */
class Vendors extends AbstractResponse
{

    /**
     * @var string
     */
    protected $_token;

    /**
     * @var string|null
     */
    protected $_transactionId;

    /**
     * Process
     *
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        parent::process();

        $body = $this->getBody();
        return $body;
    }

    /**
     * Get Required Response data keys
     *
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return [];
    }
}
