<?php
namespace Balancepay\Balancepay\Model\Config\Customer;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class TermsOptions extends AbstractSource
{
    /**
     * GetAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        $arr[] = ['label' => '15', 'value' => 15];
        $arr[] = ['label' => '30', 'value' => 30];
        $arr[] = ['label' => '45', 'value' => 45];
        $arr[] = ['label' => '60', 'value' => 60];
        return $arr;
    }
}
