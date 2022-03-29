<?php
namespace Balancepay\Balancepay\Model\Config\Customer;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class TermsOptionsLimit extends Value
{
    /**
     * TERMS_LIMIT
     */
    public const TERMS_LIMIT = 3;

    /**
     * @return TermsOptionsLimit|void
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $limits = $this->getData('groups/balancepay/fields/terms_option/value') ?? '';
        if (count($limits) > self::TERMS_LIMIT) {
            throw new LocalizedException(__('Select up to only 3 terms options.'));
        }
    }
}
