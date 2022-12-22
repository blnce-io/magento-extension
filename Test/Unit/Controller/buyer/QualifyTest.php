<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Controller\Buyer;

use Balancepay\Balancepay\Controller\Buyer\Qualify;
use Balancepay\Balancepay\Model\BalanceBuyer;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Buyers;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as modelCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Balancepay\Balancepay\Model\RequestInterface;
use Balancepay\Balancepay\Model\AbstractResponse;
use Magento\Framework\Controller\Result\Json;
use Magento\Customer\Api\Data\CustomerInterface;

class QualifyTest extends TestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;
    /**
     * @var CustomerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerInterface;
    /**
     * @var Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customer;
    /**
     * @var modelCustomer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $modelCustomer;
    /**
     * @var BalancepayConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $balancepayConfig;
    /**
     * @var RequestFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestFactory;
    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerSession;
    /**
     * @var BalanceBuyer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $balanceBuyer;
    /**
     * @var ResultFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultFactory;
    /**
     * @var AbstractResponse|\PHPUnit\Framework\MockObject\MockObject
     */
    private $abstractResponse;
    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestInterface;
    /**
     * @var Json|\PHPUnit\Framework\MockObject\MockObject
     */
    private $json;
    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactory;
    /**
     * @var CustomerFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerFactory;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->modelCustomer = $this->getMockBuilder(modelCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balancepayConfig = $this->getMockBuilder(BalancepayConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->buyers = $this->getMockBuilder(Buyers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balanceBuyer = $this->getMockBuilder(BalanceBuyer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRequestMethod', 'setTopic', 'getParams', 'process'])
            ->getMockForAbstractClass();

        $this->abstractResponse = $this->getMockBuilder(AbstractResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToken', 'getTransactionId', 'getBuyerId'])
            ->getMockForAbstractClass();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->any())->method('getRequest')
            ->willReturn($this->requestInterface);

        $this->context->expects($this->any())->method('getResultFactory')
            ->willReturn($this->resultFactory);


        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Qualify::class, [
            'context' => $this->context,
            'customer' => $this->customer,
            'balancepayConfig' => $this->balancepayConfig,
            'requestFactory' => $this->requestFactory,
            'customerSession' => $this->customerSession,
            'resultJsonFactory' => $this->resultJsonFactory,
            'customerFactory' => $this->customerFactory
        ]);
    }

    public function testExecute()
    {
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->json);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn(44554);
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('setRequestMethod')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturn($this->buyers);
        $this->buyers->expects($this->any())->method('process')->willReturn(['id' => 12]);
        $this->balancepayConfig->expects($this->any())->method('log')->willReturnSelf();
        $this->customer->expects($this->any())->method('load')->willReturnSelf();
        $this->customer->expects($this->any())->method('getDataModel')->willReturn($this->customerInterface);
        $this->customer->expects($this->any())->method('updateData')->willReturn($this->customerInterface);
        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->modelCustomer);
        $this->json->expects($this->any())->method('setData')->willReturnSelf();
        $result = $this->testableObject->execute();
    }
}









