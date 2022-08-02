<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\Exception\NoSuchEntityException;

class ConfirmedProcessor
{
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * ConfirmedProcessor constructor.
     *
     * @param Config $balancepayConfig
     */
    public function __construct(
        BalancepayConfig $balancepayConfig
    ) {
        $this->balancepayConfig = $balancepayConfig;
    }

    /**
     * ProcessConfirmedWebhook
     *
     * @param array $params
     * @param mixed $order
     * @return bool
     * @throws NoSuchEntityException
     */
    public function processConfirmedWebhook($params, $order)
    {
        $isFinanced = $params['isFinanced'] ? 1 : 0;
        $selectedPaymentMethod = (float)$params['selectedPaymentMethod'];
        $orderPayment = $order->getPayment();
        $orderPayment
            ->setAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_FINANCED, $isFinanced);
        $transactionType = $this->balancepayConfig->getIsAuth();
        $orderPayment->setAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT, $transactionType);
        $orderPayment
            ->setAdditionalInformation(BalancepayMethod::BALANCEPAY_SELECTED_PAYMENT_METHOD, $selectedPaymentMethod);
        $orderPayment->save();
        $order->save();
        return true;
    }
}
