<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;

class ChargedProcessor
{
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var BalancepayChargeFactory
     */
    private $balancepayChargeFactory;

    /**
     * @param Config $balancepayConfig
     * @param BalancepayChargeFactory $balancepayChargeFactory
     */
    public function __construct(
        BalancepayConfig $balancepayConfig,
        BalancepayChargeFactory $balancepayChargeFactory
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
    }

    /**
     * ProcessChargedWebhook
     *
     * @param array $params
     * @param mixed $order
     * @return bool
     * @throws NoSuchEntityException
     */
    public function processChargedWebhook($params, $order)
    {
        $chargeId = (string)$params['chargeId'];
        $amount = (float)$params['amount'];
        $orderPayment = $order->getPayment();

        $balancepayChargeId = $orderPayment
            ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID);

        $isBalancepayAuthCheckout = $orderPayment
            ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT);

        if (\strpos($balancepayChargeId, $chargeId) === false) {
            if (!$isBalancepayAuthCheckout
                && round((float)$order->getBaseGrandTotal()) !== round($amount)) {
                $orderPayment->setIsFraudDetected(true)->save();
                $order->setStatus(Order::STATUS_FRAUD)->save();
                throw new LocalizedException(new Phrase("The charged amount doesn't match the order total!"));
            }

            $orderPayment
                ->setTransactionId($orderPayment
                    ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID))
                ->setIsTransactionPending(false)
                ->setIsTransactionClosed(true)
                ->setAdditionalInformation(
                    BalancepayMethod::BALANCEPAY_CHARGE_ID,
                    $orderPayment->getAdditionalInformation(
                        BalancepayMethod::BALANCEPAY_CHARGE_ID,
                        $chargeId
                    ) . " \n" . $chargeId
                );

            if (!$isBalancepayAuthCheckout) {
                $orderPayment->capture(null);
            }
            $orderPayment->save();
            $order->save();

            if (!$isBalancepayAuthCheckout) {
                $invoiceId = $orderPayment->getCreatedInvoice()->getId();
                $balancepayChargeModel = $this->balancepayChargeFactory->create();
                $balancepayChargeModel->setData([
                    'charge_id' => $chargeId,
                    'invoice_id' => $invoiceId
                ]);
                $balancepayChargeModel->save();
            }
            return true;
        }
        return false;
    }
}
