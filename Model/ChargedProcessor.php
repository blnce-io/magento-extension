<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\App\ResourceConnection;
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
     * ChargedProcessor constructor.
     *
     * @param Config $balancepayConfig
     * @param BalancepayChargeFactory $balancepayChargeFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        BalancepayConfig $balancepayConfig,
        BalancepayChargeFactory $balancepayChargeFactory,
        ResourceConnection $resource
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
        $this->resource = $resource;
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
            ->setIsTransactionClosed(true);

        if (\strpos($balancepayChargeId, $chargeId) === false) {
            $orderPayment->setAdditionalInformation(
                BalancepayMethod::BALANCEPAY_CHARGE_ID,
                $orderPayment->getAdditionalInformation(
                    BalancepayMethod::BALANCEPAY_CHARGE_ID,
                    $chargeId
                ) . " \n" . $chargeId
            );
        }

        $createdInvoice = $order->getInvoiceCollection()->getFirstItem();
        if (!$isBalancepayAuthCheckout) {
            $orderPayment->capture($createdInvoice);
        }

        $orderPayment->save();
        $order->save();

        $data = null;
        if (!$isBalancepayAuthCheckout) {
            $invoiceId = $createdInvoice->getId();
            $data = ['status' => 'charged', 'invoice_id' => $invoiceId];
        } else {
            $data = ['status' => 'charged'];
        }

        $connection = $this->resource->getConnection();
        $where = ['charge_id = ?' => $chargeId];
        $tableName = $connection->getTableName("balance_charges");
        $connection->update($tableName, $data, $where);

        return true;
    }
}
