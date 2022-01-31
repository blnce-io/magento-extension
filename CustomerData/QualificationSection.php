<?php
namespace Balancepay\Balancepay\CustomerData;

use Balancepay\Balancepay\Helper\Data as BalancepayHelper;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;

/**
 * Class QualificationSection
 * @package Balancepay\Balancepay\CustomerData
 */
class QualificationSection implements SectionSourceInterface
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var BalancepayConfig
     */
    protected $balancepayConfig;

    /**
     * @var BalancepayHelper
     */
    protected $balancepayHelper;

    /**
     * @param Session $customerSession
     * @param BalancepayConfig $balancepayConfig
     * @param BalancepayHelper $balancepayHelper
     */
    public function __construct(
        Session $customerSession,
        BalancepayConfig $balancepayConfig,
        BalancepayHelper $balancepayHelper
    ) {
        $this->customerSession = $customerSession;
        $this->balancepayConfig = $balancepayConfig;
        $this->balancepayHelper = $balancepayHelper;
    }

    /**
     * @return string[]
     */
    public function getSectionData()
    {
        $status = false;
        $showButton = true;
        $showCredit = false;
        $creditLimit = '$0.00';
        $buyerResponse = $this->balancepayHelper->getBuyerAmount();
        if (!empty($buyerResponse['qualificationStatus']) && $buyerResponse['qualificationStatus'] == 'completed'){
            $status = true;
            $showButton = false;
            $showCredit = true;
            $creditLimit =  $this->balancepayHelper->formattedAmount($buyerResponse['qualification']['creditLimit'] ?? 0);
        }
        return [
            'creditLimit' => 'Terms Limit: '.$creditLimit,
            'status' => $status, // $buyerResponse['qualificationStatus'] ?? ''
            'showButton' => $showButton,
            'showCredit' => $showCredit
        ];
    }
}
