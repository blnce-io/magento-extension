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

namespace Balancepay\Balancepay\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Balancepay allowedPaymentMethods source model.
 */
class AllowedPaymentMethods implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'creditCard' => __('Credit Card'),
            'payWithTerms' => __('Pay With Terms'),
            'invoice' => __('Invoice'),
            'achDebit' => __('ACH Debit'),
            'achCredit' => __('ACH Credit')
        ];
    }
}
