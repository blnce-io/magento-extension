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
use Balancepay\Balancepay\Model\Response\Buyers;

/**
 * Balancepay transactions/cancel response model.
 */
class Cancel extends AbstractResponse
{

    /**
     * @return array|Cancel
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process()
    {
        parent::process();

        return $this->getBody();
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
}
