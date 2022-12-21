<?php

namespace Balancepay\Balancepay\Test\Unit\Model\ResourceModel\BalancepayCharge;

use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use Balancepay\Balancepay\Model\BalancepayCharge;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Select;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use ReflectionException;

class CollectionTest extends TestCase
{
    /**
     * Object Manager Mock Object
     *
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Collection testable object
     *
     * @var Collection
     */
    private $testableObject;

    /**
     * Entity Factory Mock Object
     *
     * @var MockObject
     */
    private $entityFactoryMock;

    /**
     * Logger Mock Object
     *
     * @var MockObject
     */
    private $loggerMock;

    /**
     * FetchStrategy Mock Object
     *
     * @var MockObject
     */
    private $fetchStrategyMock;

    /**
     * Manager Mock Object
     *
     * @var MockObject
     */
    private $eventManagerMock;

    /**
     * DB Adapter Mock Object
     *
     * @var MockObject
     */
    private $connectionMock;

    /**
     * Resource Mock Object
     *
     * @var MockObject
     */
    private $resourceMock;

    /**
     * Select Mock Object
     *
     * @var MockObject
     */
    private $selectMock;

    public function testConstructMethod(): void
    {
        $reflectionMethod = new ReflectionMethod($this->testableObject, '_construct');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($this->testableObject);
        $this->assertNull($result);
    }

    public function testGetChargeAndStatus()
    {
        $dataObject = new DataObject(['charge_id' => 1, 'status' => 'charged']);
        $this->chargeCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->chargeCollection->expects($this->any())->method('getFirstItem')
            ->willReturn($dataObject);
        $dataObject->getData();
        $this->testableObject->getChargeAndStatus(12);
    }

    public function testGetChargeId()
    {
        $this->chargeCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->chargeCollection->expects($this->any())->method('getFirstItem')
            ->willReturn(new DataObject(['charge_id' => 1]));
        $this->testableObject->getChargeId(12);
    }

    /**
     * This method is executed before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->selectMock = $this->createMock(Select::class);
        $this->entityFactoryMock = $this->createMock(EntityFactoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->fetchStrategyMock = $this->createMock(FetchStrategyInterface::class);
        $this->eventManagerMock = $this->createMock(ManagerInterface::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->resourceMock = $this->createMock(AbstractDb::class);
        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()->setMethods(['getChargeId', 'getStatus'])
            ->getMock();

        $this->chargeCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balancepayCharge = $this->getMockBuilder(BalancepayCharge::class)
            ->disableOriginalConstructor()->setMethods(['getChargeId', 'getStatus'])
            ->getMock();

        $this->resourceMock->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->connectionMock->method('select')
            ->willReturn($this->selectMock);

        $this->objectManager = new ObjectManager($this);
        $this->testableObject = $this->objectManager->getObject(
            Collection::class,
            [
                'entityFactory' => $this->entityFactoryMock,
                'logger' => $this->loggerMock,
                'fetchStrategy' => $this->fetchStrategyMock,
                'eventManager' => $this->eventManagerMock,
                'connection' => $this->connectionMock,
                'resource' => $this->resourceMock
            ]
        );
    }
}
