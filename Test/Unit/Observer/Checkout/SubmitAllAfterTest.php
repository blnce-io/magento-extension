<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Observer\Checkout;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Observer\Checkout\SubmitAllAfter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubmitAllAfterTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Config|MockObject
     */
    private $balancepayConfig;

    /**
     * @var AuthorizeCommand|MockObject
     */
    private $authorizeCommand;

    /**
     * @var InvoiceService|MockObject
     */
    private $invoiceService;

    /**
     * @var CaptureCommand|MockObject
     */
    private $captureCommand;

    /**
     * @var TransactionFactory|MockObject
     */
    private $transactionFactory;

    /**
     * @var Phrase|MockObject
     */
    private $phrase;

    /**
     * @var OrderPaymentInterface|MockObject
     */
    private $orderpaymentinterface;

    /**
     * @var LocalizedException|MockObject
     */
    private $localizedException;

    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIsAuth',
                'log'
            ])
            ->getMockForAbstractClass();

        $this->authorizeCommand = $this->getMockBuilder(AuthorizeCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'execute'
            ])
            ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();

        $this->transaction = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder'])
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getPayment',
                'getBillingAddress',
                'getBaseGrandTotal',
                'save'
            ])->addMethods([
                'getMethod'
            ])
            ->getMock();

        $this->captureCommand = $this->getMockBuilder(CaptureCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'execute'
            ])
            ->getMockForAbstractClass();

        $this->phrase = $this->getMockBuilder(Phrase::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->exception = $this->getMockBuilder(\Exception::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->invoiceService = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->transactionFactory = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderpaymentinterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'setIsTransactionClosed',
                'setTransactionId',
                'addTransactionCommentsToOrder',
                'addTransaction',
                'prependMessage',
                'save'
            ])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(SubmitAllAfter::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'authorizeCommand' => $this->authorizeCommand,
            'captureCommand' => $this->captureCommand,
            'invoiceService' => $this->invoiceService,
            'transactionFactory' => $this->transactionFactory
        ]);
    }

    public function testExecuteOrderNull(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getOrder')->willReturn(null);
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    public function testExecuteIsNotBalancepayMethod(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getOrder')->willReturn($this->order);
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('getMethod')->willReturn('string');
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    public function testExecuteGetBalanceMethodNoAuth(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getOrder')->willReturn($this->order);
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderpaymentinterface);
        $this->balancepayConfig->expects($this->any())->method('getIsAuth')->willReturn(0);
        $this->orderpaymentinterface->expects($this->any())->method('getMethod')->willReturn('balancepay');
        $this->orderpaymentinterface->expects($this->any())->method('getAdditionalInformation')
            ->with(BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID)->willReturn('string');
        $this->order->expects($this->any())->method('getBaseGrandTotal')->willReturn(2.56);
        $this->captureCommand->expects($this->any())->method('execute')
            ->with(
                $this->orderpaymentinterface,
                2.56,
                $this->order
            )->willReturn($this->phrase);
        $this->orderpaymentinterface->expects($this->any())->method('setIsTransactionClosed')
            ->with(0)->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('setTransactionId')
            ->with('string')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('addTransaction')->willReturn($this->transaction);
        $this->orderpaymentinterface->expects($this->any())->method('prependMessage')->willReturn('string');
        $this->orderpaymentinterface->expects($this->any())->method('addTransactionCommentsToOrder')
            ->with($this->transaction, 'string')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    public function testExecuteGetBalanceMethodAuth(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getOrder')->willReturn($this->order);
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderpaymentinterface);
        $this->balancepayConfig->expects($this->any())->method('getIsAuth')->willReturn(1);
        $this->orderpaymentinterface->expects($this->any())->method('getMethod')->willReturn('balancepay');
        $this->orderpaymentinterface->expects($this->any())->method('getAdditionalInformation')->willReturn('string');
        $this->order->expects($this->any())->method('getBaseGrandTotal')->willReturn(2.56);
        $this->authorizeCommand->expects($this->any())->method('execute')->willReturn($this->phrase);
        $this->orderpaymentinterface->expects($this->any())->method('setIsTransactionClosed')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('setTransactionId')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('addTransaction')->willReturn($this->transaction);
        $this->orderpaymentinterface->expects($this->any())->method('prependMessage')->willReturn('string');
        $this->orderpaymentinterface->expects($this->any())->method('addTransactionCommentsToOrder')
            ->with($this->transaction, 'string')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    public function testExecuteGetBalanceMethodAuthException(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willThrowException(new \Exception());
        $this->balancepayConfig->expects($this->any())->method('log')->willReturnSelf();
        $this->expectException(LocalizedException::class);
    }
}
