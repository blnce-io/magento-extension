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
namespace Balancepay\Balancepay\Block;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\View\Element\Template\Context;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  State       $appState
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        State $appState,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->appState = $appState;
    }

    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];

        $info = $this->getInfo();

        if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML) {
            if (($checkoutToken = $info->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHECKOUT_TOKEN))) {
                $data[(string)__('Checkout Token')] = $checkoutToken;
            }
            if (($transationId = $info->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID))) {
                $data[(string)__('Transaction ID')] = $transationId;
            }
            if (($chargeId = $info->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID))) {
                $data[(string)__('Is Charged')] = __('Yes');
                $data[(string)__('Charge ID')] = $chargeId;
            } else {
                $data[(string)__('Is Charged')] = __('No');
            }
            $data[(string)__('Is Financed')] = (int) $info->getAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_FINANCED) ? __('Yes') : __('No');
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
