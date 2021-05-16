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
 * Balancepay abstract api model.
 */
abstract class AbstractApi
{
    /**
     * @var Config
     */
    protected $_balancepayConfig;

    /**
     * Object initialization.
     *
     * @param Config           $config
     */
    public function __construct(
        Config $balancepayConfig
    ) {
        $this->_balancepayConfig = $balancepayConfig;
    }

    /**
     * @method amountFormat
     * @param  float|int              $amount
     * @return float
     */
    protected function amountFormat($amount)
    {
        return (float) number_format((float)$amount, 2, '.', '');
    }
}
