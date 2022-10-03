<?php
namespace Balancepay\Balancepay\Test\Unit\ViewModel;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Balancepay\Balancepay\ViewModel\InvoiceForm;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class InvoiceFormTest extends TestCase
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
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->collection = $this->createMock(Collection::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(InvoiceForm::class, [
            'collection' => $this->collection,
            'request' => $this->request
        ]);
    }

    public function testEnableCreditMemo(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')->with('invoice_id')
            ->willReturn(null);

        $result = $this->testableObject->enableCreditMemo();
        $this->assertIsBool($result);
    }

    public function testEnableCreditMemoWithInvoice(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(1);

        $this->collection->expects($this->any())
            ->method('getChargeAndStatus')
            ->willReturn(false);

        $result = $this->testableObject->enableCreditMemo();
        $this->assertIsBool($result);
    }

    public function testEnableCreditMemoWithInvoiceWithCharge(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(1);

        $this->collection->expects($this->any())
            ->method('getChargeAndStatus')
            ->willReturn(true);

        $result = $this->testableObject->enableCreditMemo();
        $this->assertIsBool($result);
    }
}
