<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\BalancepayChargeFactory;
use Balancepay\Balancepay\Model\BalancepayCharge;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Framework\Exception\LocalizedException;

class ChargedProcessorTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Phrase|MockObject
     */
    private $phrase;

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIsAuth'])->getMock();

        $this->balancepayChargeFactory = $this->getMockBuilder(BalancepayChargeFactory::class)
            ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();

        $this->balancepayCharge = $this->getMockBuilder(BalancepayCharge::class)
            ->disableOriginalConstructor()->onlyMethods(['save', 'setData'])->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()->onlyMethods(['save', 'getPayment', 'getBaseGrandTotal', 'setStatus'])->getMock();

        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'save',
                'setIsFraudDetected',
                'setTransactionId',
                'setIsTransactionPending',
                'setIsTransactionClosed',
                'capture',
                'getCreatedInvoice',
                'getId'
            ])->onlyMethods(['setAdditionalInformation'])->getMockForAbstractClass();


        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(ChargedProcessor::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'balancepayChargeFactory' => $this->balancepayChargeFactory
        ]);
    }

    /**
     * @return void
     */
    public function testProcessChargedWebhookChargeIdNotEqualNonAuthCheckoutAmountDifferent()
    {
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->exactly(2))->method('getAdditionalInformation')
            ->withConsecutive([BalancepayMethod::BALANCEPAY_CHARGE_ID], [BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT])
            ->willReturnOnConsecutiveCalls(14235, false);
        $this->order->expects($this->any())->method('getBaseGrandTotal')->willReturn(13.30);
        $this->orderPaymentInterface->expects($this->any())->method('setIsFraudDetected')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->expectException(LocalizedException::class);
        $result = $this->testableObject->processChargedWebhook(
            [
                'chargeId' => 14234,
                'amount' => 12.40
            ],
            $this->order
        );
        $this->assertIsBool($result);
    }

    public function testProcessChargedWebhookChargeIdNotEqualNonAuthCheckoutAmountSame()
    {
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('getAdditionalInformation')
            ->withConsecutive([BalancepayMethod::BALANCEPAY_CHARGE_ID], [BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT])
            ->willReturnOnConsecutiveCalls(14235, false);
        $this->order->expects($this->any())->method('getBaseGrandTotal')->willReturn(12.40);
        $this->orderPaymentInterface->expects($this->any())->method('setIsFraudDetected')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setTransactionId')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionPending')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionClosed')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setAdditionalInformation')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('capture')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('getCreatedInvoice')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('getId')->willReturn(5);
        $this->balancepayChargeFactory->expects($this->any())->method('create')->willReturn($this->balancepayCharge);
        $this->balancepayCharge->expects($this->any())->method('setData')->willReturnSelf();
        $this->balancepayCharge->expects($this->any())->method('save')->willReturnSelf();
        $result = $this->testableObject->processChargedWebhook(
            [
                'chargeId' => 14234,
                'amount' => 12.40
            ],
            $this->order
        );
        $this->assertIsBool($result);
    }

    public function testProcessChargedWebhookChargeIdNotEqualAuthCheckout()
    {
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('getAdditionalInformation')
            ->withConsecutive(
                [BalancepayMethod::BALANCEPAY_CHARGE_ID],
                [BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT],
                [BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID]
            )
            ->willReturnOnConsecutiveCalls(14235, true, 'txn_83782747234982934');
        $this->orderPaymentInterface->expects($this->any())->method('setTransactionId')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionPending')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionClosed')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setAdditionalInformation')->willReturnSelf();
        $result = $this->testableObject->processChargedWebhook(
            [
                'chargeId' => 14234,
                'amount' => 12.40
            ],
            $this->order
        );
        $this->assertIsBool($result);
    }

    public function testProcessChargedWebhookChargeIdEqualAuthCheckout()
    {
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('getAdditionalInformation')
            ->withConsecutive(
                [BalancepayMethod::BALANCEPAY_CHARGE_ID],
                [BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT],
                [BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID]
            )
            ->willReturnOnConsecutiveCalls(14234, true, 'txn_83782747234982934');
        $result = $this->testableObject->processChargedWebhook(
            [
                'chargeId' => 14234,
                'amount' => 12.40
            ],
            $this->order
        );
        $this->assertIsBool($result);
    }
}
