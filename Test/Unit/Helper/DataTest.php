<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Helper;

use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Framework\App\Http\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Helper\Data as HelperData;

class DataTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    protected function setUp(): void
    {
        $this->mpProductCollection = $this->getMockBuilder(MpProductCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(HelperData::class, [
            'requestFactory' => $this->mpProductCollection,
            'helper' => $this->helperData,
            'customer' => $this->context,
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
        $this->customerRepositoryInterface->expects($this->any())->method('getById')->willReturn($this->customerInterface);
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













