<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model\Response;

use Balancepay\Balancepay\Model\Response\Factory;
use Balancepay\Balancepay\Model\Request\Refunds;
use Magento\Customer\Model\Address;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{

    /**
     * @var Address|MockObject
     */
    private $_objectManager;

    protected function setUp(): void
    {
        $this->_objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Factory::class, [
            'objectManager' => $this->_objectManager
        ]);
    }

    public function testCreate()
    {
        $this->_objectManager->expects($this->any())->method('create');
        $result = $this->testableObject->create(Refunds::class);
        $this->assertEquals($this->testableObject, $result);
    }
}
