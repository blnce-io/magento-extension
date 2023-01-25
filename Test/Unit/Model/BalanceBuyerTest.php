<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\BalanceBuyer;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Psr\Log\LoggerInterface;
use Balancepay\Balancepay\Model\Config;
use Magento\Customer\Api\Data\CustomerInterface;

class BalanceBuyerTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $this->helperData = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()->getMock();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()->getMock();

        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();

        $this->customerInterface = $this->getMockBuilder(\Magento\Customer\Model\Data\Customer::class)
            ->disableOriginalConstructor()->setMethods(['__toArray', 'setCustomAttribute'])->getMock();

        $this->customerResource = $this->getMockBuilder(CustomerResource::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(BalanceBuyer::class, [
            'requestFactory' => $this->requestFactory,
            'helper' => $this->helperData,
            'customer' => $this->customer,
            'customerFactory' => $this->customerFactory,
            'customerSession' => $this->session,
            'customerRepositoryInterface' => $this->customerRepositoryInterface,
            'logger' => $this->loggerInterface,
            'balancepayConfig' => $this->config
        ]);
    }

    /**
     * @return void
     */
    public function testUpdateCustomerBalanceBuyerId()
    {
        $this->session->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('load')->willReturnSelf();
        $this->customer->expects($this->any())->method('getDataModel')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->customer->expects($this->any())->method('updateData')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerResource);
        $this->customerResource->expects($this->any())->method('saveAttribute')->willReturnSelf();
        $result = $this->testableObject->updateCustomerBalanceBuyerId(
            'RnGw68WL1qFDKJJJ5Qnnhn38dVrEejcRGdJvA',
            0
        );
    }

    public function testGetCustomerBalanceBuyerId()
    {
        $this->session->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn(5);
        $this->customerRepositoryInterface->expects($this->any())->method('getById')
            ->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('__toArray')->willReturn([
            'custom_attributes' => [
                'buyer_id' => [
                    'value' => 'byc_34234234jsdf3423'
                ]
            ]
        ]);
        $result = $this->testableObject->getCustomerBalanceBuyerId();
        $this->assertEquals('byc_34234234jsdf3423', $result);
    }

    public function testGetCustomerBalanceBuyerIdNoId()
    {
        $this->session->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn('');
        $result = $this->testableObject->getCustomerBalanceBuyerId();
        $this->assertNull($result);
    }
}
