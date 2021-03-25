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
 * Balancepay webhooks response model.
 */
class Webhooks extends AbstractResponse
{
    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return ['id', 'topic','address'];
    }
}
