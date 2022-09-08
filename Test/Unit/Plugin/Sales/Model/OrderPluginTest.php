<?php

namespace Balancepay\Balancepay\Test\Unit\Plugin\Sales\Model;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Balancepay\Balancepay\Plugin\Sales\Model\OrderPlugin;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;

class OrderPluginTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Collection|MockObject
     */
    private $collection;
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Order|MockObject
     */
    private $order;

    protected function setUp(): void
    {
        $this->collection = $this->createMock(Collection::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->order = $this->createMock(Order::class);

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(OrderPlugin::class, [
            'collection' => $this->collection,
            'request' => $this->request
        ]);
    }

    public function testAfterCanCreditmemo(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(null);

        $result = $this->testableObject->afterCanCreditmemo($this->order, true);
        $this->assertIsBool($result);
    }

    public function testAfterCanCreditmemoWithInvoice(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(1);

        $this->collection->expects($this->any())
            ->method('getChargeAndStatus')
            ->willReturn(false);

        $result = $this->testableObject->afterCanCreditmemo($this->order, true);
        $this->assertIsBool($result);
    }

    public function testAfterCanCreditmemoWithInvoiceWithCharge(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(1);

        $this->collection->expects($this->any())
            ->method('getChargeAndStatus')
            ->willReturn(true);

        $result = $this->testableObject->afterCanCreditmemo($this->order, true);
        $this->assertIsBool($result);
    }
}
