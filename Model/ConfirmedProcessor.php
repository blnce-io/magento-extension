<?php

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

class ConfirmedProcessor
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var BalancepayChargeFactory
     */
    private $balancepayChargeFactory;

    /**
     * ConfirmedProcessor constructor.
     *
     * @param Config $balancepayConfig
     * @param BalancepayChargeFactory $balancepayChargeFactory
     */
    public function __construct(
        BalancepayConfig $balancepayConfig,
        BalancepayChargeFactory $balancepayChargeFactory,
        LoggerInterface $logger
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->balancepayChargeFactory = $balancepayChargeFactory;
        $this->logger = $logger;
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
        $chargeIds = $params['chargeIds'];
        $orderPayment = $order->getPayment();
        $orderPayment
            ->setAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_FINANCED, $isFinanced);
        $isAuth = $this->balancepayConfig->getIsAuth();
        $orderPayment->setAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT, $isAuth);

        if (!$isAuth) {
            if (count($chargeIds) != 1) {
                throw new LocalizedException(new Phrase("Exactly 1 charge is expected for a non-auth order"));
            }

            $orderPayment->setAdditionalInformation(
                BalancepayMethod::BALANCEPAY_CHARGE_ID,
                $chargeIds[0]
            );

            $invoice = $orderPayment->getOrder()->prepareInvoice();
            $invoice->register();
            $orderPayment->getOrder()->addRelatedObject($invoice);

            $orderPayment->save();
            $order->save();

            $invoiceId = $order->getInvoiceCollection()->getFirstItem()->getId();
            $balancepayChargeModel = $this->balancepayChargeFactory->create();
            $balancepayChargeModel->setData([
                'charge_id' => $chargeIds[0],
                'invoice_id' => $invoiceId,
                'status' => 'pending'
            ]);

            $balancepayChargeModel->save();
        } else {
            $orderPayment->save();
            $order->save();
        }

        return true;
    }
}
