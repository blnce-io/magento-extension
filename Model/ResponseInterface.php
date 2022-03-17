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

namespace Balancepay\Balancepay\Model;

/**
 * Balancepay response interface.
 */
interface ResponseInterface
{
    /**
     * Process
     *
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
