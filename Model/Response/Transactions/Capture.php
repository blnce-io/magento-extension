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

namespace Balancepay\Balancepay\Model\Response\Transactions;

use Balancepay\Balancepay\Model\AbstractResponse;

/**
 * Balancepay transactions/capture response model.
 */
class Capture extends AbstractResponse
{
    /**
     * @var mixed
     */
    private $charges;

    /**
     * Process
     *
     * @return array|Capture
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process()
    {
        parent::process();
        $body = $this->getBody();
        $this->charges = $body['charges'];
        return $body;
    }

    /**
     * Determine if request succeed or failed.
     *
     * @return bool
     */
    protected function getRequestStatus()
    {
        $httpStatus = $this->getStatus();
        if (!in_array($httpStatus, [201])) {
            return false;
        }

        return true;
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
     * Get Charges
     *
     * @return mixed
     */
    public function getCharges()
    {
        return $this->charges;
    }
}
