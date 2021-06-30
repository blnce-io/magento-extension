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

/**
 * Balancepay transactions response model.
 */
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
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        parent::process();

        $body = $this->getBody();
        $this->_token = $body['token'];
        $this->_transactionId = isset($body['id']) ? $body['id'] : null;

        return $this;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        if ($this->_balancepayConfig->getIsAuth()) {
            return ['token', 'id'];
        }
        return ['token'];
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @return string|null
     */
    public function getTransactionId()
    {
        return $this->_transactionId;
    }
}
