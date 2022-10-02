<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\BalancepayChargeFactory;
use Balancepay\Balancepay\Model\BalancepayCharge;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
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
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

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

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()->onlyMethods(['getConnection'])->getMock();

        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()->onlyMethods(['getTableName'])->getMockForAbstractClass();

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
            'balancepayChargeFactory' => $this->balancepayChargeFactory,
            'resource' => $this->resourceConnection
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
        $this->orderPaymentInterface->expects($this->once())->method('setIsFraudDetected')->with(true)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('setStatus')->with(Order::STATUS_FRAUD)->willReturnSelf();
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
            ->withConsecutive(
                [BalancepayMethod::BALANCEPAY_CHARGE_ID],
                [BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT],
                [BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID]
            )->willReturnOnConsecutiveCalls(14235, false, 'txn_83782747234982934');
        $this->order->expects($this->any())->method('getBaseGrandTotal')->willReturn(12.40);
        $this->orderPaymentInterface->expects($this->any())->method('setIsFraudDetected')->with(true)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('setStatus')->with(Order::STATUS_FRAUD)->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setTransactionId')->with('txn_83782747234982934')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionPending')->with(false)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionClosed')->with(true)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setAdditionalInformation')
            ->with(
                BalancepayMethod::BALANCEPAY_CHARGE_ID,
                " \n14234"
            )->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('capture')->with(null)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('getCreatedInvoice')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('getId')->willReturn(5);
        $this->balancepayChargeFactory->expects($this->any())->method('create')->willReturn($this->balancepayCharge);
        $this->balancepayCharge->expects($this->any())->method('setData')->with([
            'charge_id' => 14234,
            'invoice_id' => 5,
            'status' => 'charged'
        ])->willReturnSelf();
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

        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturn($this->adapterInterface);
        $this->adapterInterface->expects($this->any())->method('getTableName')->willReturn('tableName');
        $this->orderPaymentInterface->expects($this->any())->method('setTransactionId')->with('txn_83782747234982934')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionPending')->with(false)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setIsTransactionClosed')->with(true)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setAdditionalInformation')->with(
            BalancepayMethod::BALANCEPAY_CHARGE_ID,
            " \n14234"
        )->willReturnSelf();
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
