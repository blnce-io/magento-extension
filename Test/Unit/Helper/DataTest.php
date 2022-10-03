<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Helper;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Api\Data\CustomerInterface;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Helper\Data as HelperData;
use \Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\Collection;

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

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMockForAbstractClass();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $this->balancepayConfig = $this->getMockBuilder(BalancepayConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $this->pricingHelper = $this->getMockBuilder(PricingHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(HelperData::class, [
            'mpProductCollectionFactory' => $this->mpProductCollection,
            'appContext' => $this->context,
            'customerSession' => $this->session,
            'customerRepositoryInterface' => $this->customerRepositoryInterface,
            'requestFactory' => $this->requestFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'pricingHelper' => $this->pricingHelper
        ]);
    }

    /**
     * @return void
     */
    public function testGetBalanceVendors()
    {
        $this->mpProductCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->collection);
        $this->collection->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();
        $this->collection->expects($this->any())->method('getFirstItem')
            ->willReturn();
        $result = $this->testableObject->getBalanceVendors('12');
    }

    public function testGetCustomerSessionId()
    {
        $this->appContext->expects($this->any())->method('getValue')
            ->willReturn(12);
        $result = $this->testableObject->getCustomerSessionId();
    }

    public function testGetBuyerAmountNoBuyerId()
    {
        $this->customerSession->expects($this->any())->method('getBuyerId')
            ->willReturn(0);
        $this->appContext->expects($this->any())->method('getValue')
            ->willReturn(12);
        $this->customerRepositoryInterface->expects($this->any())->method('getById')
            ->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('__toArray')
            ->willReturn([
                'custom_attributes' => [
                    'buyer_id' => [
                        'value' => 12
                    ]
                ]
            ]);
        $this->customerSession->expects($this->any())->method('setBuyerId')
            ->willReturnSelf();
        $result = $this->testableObject->getBuyerAmount();
    }
}













