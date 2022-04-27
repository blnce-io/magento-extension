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

namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Model\AbstractResponse;
use Magento\Framework\Exception\LocalizedException;

class Transactions extends AbstractResponse
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
     * @var mixed|null
     */
    private $_buyerId;

    /**
     * Process
     *
     * @return AbstractResponse
     * @throws LocalizedException
     */
    public function process()
    {
        parent::process();

        $body = $this->getBody();
        $this->_token = isset($body['token']) ? $body['token'] : '';
        $this->_transactionId = isset($body['id']) ? $body['id'] : null;
        $this->_buyerId = isset($body['buyer']['id']) ? $body['buyer']['id'] : null;

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
     * GetToken
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * GetTransactionId
     *
     * @return string|null
     */
    public function getTransactionId()
    {
        return $this->_transactionId;
    }

    /**
     * Get Buyer Id
     *
     * @return string|null
     */
    public function getBuyerId()
    {
        return $this->_buyerId;
    }
}
