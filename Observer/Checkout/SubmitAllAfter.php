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

namespace Balancepay\Balancepay\Observer\Checkout;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;

class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var AuthorizeCommand
     */
    private $authorizeCommand;

    /**
     * @var CaptureCommand
     */
    private $captureCommand;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * Constructor
     *
     * @param Config $balancepayConfig
     * @param AuthorizeCommand $authorizeCommand
     * @param CaptureCommand $captureCommand
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        Config $balancepayConfig,
        AuthorizeCommand $authorizeCommand,
        CaptureCommand $captureCommand,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->authorizeCommand = $authorizeCommand;
        $this->captureCommand = $captureCommand;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        try {

            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();

            /** @var OrderPayment $payment */
            $orderPayment = $order->getPayment();

            if ($orderPayment->getMethod() !== BalancepayMethod::METHOD_CODE) {
                return $this;
            }

            $transactionId = $orderPayment->getAdditionalInformation(
                BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID
            );

            if ($transactionId && $this->balancepayConfig->getIsAuth()) {
                $message = $this->authorizeCommand->execute(
                    $orderPayment,
                    $order->getBaseGrandTotal(),
                    $order
                );
                $orderPayment->setIsTransactionClosed(0);
                $orderPayment->setTransactionId($transactionId);
                $orderPayment->addTransactionCommentsToOrder(
                    $orderPayment->addTransaction(Transaction::TYPE_AUTH),
                    $orderPayment->prependMessage($message)
                );

                $orderPayment->save();
                $order->save();
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log(
                'SubmitAllAfter::execute() - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                'error'
            );
            throw new LocalizedException(
                __('Your order have been placed, but there has been an error on the server, please contact us.')
            );
        }

        return $this;
    }
}
