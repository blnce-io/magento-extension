<?php
namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Model\AbstractResponse;

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
     * GetRequiredResponseDataKeys
     *
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return [];
    }
}
