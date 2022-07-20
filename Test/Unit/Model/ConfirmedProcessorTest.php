<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Observer\Checkout;

use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Observer\Checkout\SubmitAllAfter;
use Magento\Framework\Phrase;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
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
     * Test
     *
     * @covers ::execute
     * @covers ::__construct
     */
    public function testExecute(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getOrder')->willReturn($this->order);
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderpaymentinterface);
        $this->orderpaymentinterface->expects($this->any())->method('getMethod')->willReturn('string');
        $this->order->expects($this->any())->method('getAdditionalInformation')->willReturn('string');
        $this->order->expects($this->any())->method('getBaseGrandTotal')->willReturn(2.56);
        $this->order->expects($this->any())->method('setIsTransactionClosed')->willReturn($this->order);
        $this->order->expects($this->any())->method('setTransactionId')->willReturn($this->order);
        $this->order->expects($this->any())->method('addTransactionCommentsToOrder')->willReturn($this->order);
        $this->order->expects($this->any())->method('addTransaction')->willReturn(null);
        $this->order->expects($this->any())->method('prependMessage')->willReturn('string');
        $this->balancepayConfig->expects($this->any())->method('getIsAuth')->willReturn('true');
        $this->authorizeCommand->expects($this->any())->method('execute')->willReturn($this->phrase);
        $this->captureCommand->expects($this->any())->method('execute')->willReturn($this->phrase);
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIsAuth'
            ])
            ->getMockForAbstractClass();

        $this->authorizeCommand = $this->getMockBuilder(AuthorizeCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'execute'
            ])
            ->getMockForAbstractClass();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMockForAbstractClass();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder'])
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getPayment',
                'getBillingAddress',
                'getBaseGrandTotal'
            ])->addMethods([
                'getAdditionalInformation',
                'setIsTransactionClosed',
                'setTransactionId',
                'addTransactionCommentsToOrder',
                'addTransaction',
                'prependMessage',
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

        $this->invoiceService = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->transactionFactory = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderpaymentinterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
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
}
