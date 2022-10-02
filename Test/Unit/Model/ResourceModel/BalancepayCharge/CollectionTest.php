<?php

namespace Balancepay\Balancepay\Test\Unit\Model\ResourceModel\BalancepayCharge;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Select;
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

    /**
     * Test
     *
     * @covers ::_construct
     *
     * @return void
     * @throws ReflectionException
     */
    public function testConstructMethod(): void
    {
        $reflectionMethod = new ReflectionMethod($this->testableObject, '_construct');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($this->testableObject);
        $this->assertNull($result);
    }
}
