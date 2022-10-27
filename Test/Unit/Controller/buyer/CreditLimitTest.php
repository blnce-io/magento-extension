<?php
declare(strict_types=1);
namespace Balancepay\Balancepay\Test\Unit\Controller\Buyer;

use Balancepay\Balancepay\Controller\Buyer\CreditLimit;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;

class CreditLimitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(CreditLimit::class, [
            'context' => $this->context,
            'resultJsonFactory' => $this->resultJsonFactory
        ]);
    }

    public function testExecute()
    {
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->json);
        $this->json->expects($this->any())->method('setData')->willReturn($this->json);
        $result = $this->testableObject->execute();
    }
}









