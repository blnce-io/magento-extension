<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Observer;

use Balancepay\Balancepay\Model\BalancepayChargeFactory;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Observer\SalesOrderInvoiceSaveAfter;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Model\BalancepayCharge;

class SalesOrderInvoiceSaveAfterTest extends TestCase
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
     * @var BalancepayCharge|MockObject
     */
    private $balancepayCharge;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * @var BalancepayChargeFactory|MockObject
     */
    private $balancepayChargeFactory;

    /**
     * Test
     *
     */
    public function testExecute(): void
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getInvoice')->willReturn($this->invoice);
        $this->invoice->expects($this->any())->method('getId')->willReturn('44555');
        $this->registry->expects($this->any())->method('registry')->withConsecutive(['charge_id'], ['charge_status'])
            ->willReturnOnConsecutiveCalls('12', 1);
        $this->balancepayChargeFactory->expects($this->any())->method('create')->willReturn($this->balancepayCharge);
        $this->balancepayCharge->expects($this->any())->method('setData')->willReturn($this->balancepayCharge);
        $this->balancepayCharge->expects($this->any())->method('save')->willReturn($this->balancepayCharge);
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    protected function setUp(): void
    {
        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMockForAbstractClass();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getInvoice'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()->setMethods(['registry'])
            ->getMockForAbstractClass();

        $this->balancepayCharge = $this->getMockBuilder(BalancepayCharge::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balancepayChargeFactory = $this->getMockBuilder(BalancepayChargeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoice = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(SalesOrderInvoiceSaveAfter::class, [
            'registry' => $this->registry,
            'balancepayChargeFactory' => $this->balancepayChargeFactory
        ]);
    }
}
