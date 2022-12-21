<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Observer\CustomerRegisterSuccess;
use Magento\Framework\Session\SessionManagerInterface;
use Balancepay\Balancepay\Model\BalanceBuyer;
use Balancepay\Balancepay\Model\Config;

class CustomerRegisterSuccessTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    public function testExecute()
    {
        $this->observer->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn(1);
        $this->sessionManager->expects($this->any())->method('getBalanceBuyerId')->willReturn('byc_4ksl4342342342');
        $this->balanceBuyer->expects($this->any())->method('updateCustomerBalanceBuyerId')->willReturn(null);
        $this->sessionManager->expects($this->any())->method('unsBalanceBuyerId')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $this->observer->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn(1);
        $this->sessionManager->expects($this->any())->method('getBalanceBuyerId')->willThrowException(new \Exception());
        $this->config->expects($this->any())->method('log')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }

    protected function setUp(): void
    {
        $this->sessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBalanceBuyerId', 'unsBalanceBuyerId'])
            ->getMockForAbstractClass();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomer'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()->getMock();

        $this->balanceBuyer = $this->getMockBuilder(BalanceBuyer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateCustomerBalanceBuyerId'])
            ->getMockForAbstractClass();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['log'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(CustomerRegisterSuccess::class, [
            'coreSession' => $this->sessionManager,
            'balanceBuyer' => $this->balanceBuyer,
            'balancepayConfig' => $this->config
        ]);
    }
}
