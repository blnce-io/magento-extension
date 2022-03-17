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

namespace Balancepay\Balancepay\Plugin\Sales\Model\Order\Payment\State;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

class AuthorizeCommand
{
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @method __construct
     * @param  Config      $balancepayConfig
     */
    public function __construct(
        Config $balancepayConfig
    ) {
        $this->balancepayConfig = $balancepayConfig;
    }

    /**
     * AfterExecute
     *
     * @param Order\Payment\State\AuthorizeCommand $authorizeCommand
     * @param mixed $result
     * @param OrderPaymentInterface $payment
     * @param mixed $amount
     * @param OrderInterface $order
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand $authorizeCommand,
        $result,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        if ($this->balancepayConfig->isActive() && $payment->getMethod() === BalancepayMethod::METHOD_CODE) {
            $order->setState(Order::STATE_NEW)->setStatus('pending');
        }
        return $result;
    }
}
