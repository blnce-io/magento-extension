<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Controller\Webhook\Transaction;

use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundFailed;
use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\QueueFactory;
use Balancepay\Balancepay\Model\RequestInterface;
use Magento\Framework\App\RequestInterface as BalanceRequestInterface;
use Balancepay\Balancepay\Model\Queue;
use Magento\Framework\App\Action\AbstractAction;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\WebhookRequestProcessor;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Controller\ResultInterface;


class RefundFailedTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Config|MockObject
     */
    private $balancepayConfig;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManager;

    /**
     * @var RequestFactory|MockObject
     */
    private $requestFactory;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customerInterface;

    /**
     * @var Json|MockObject
     */
    private $json;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestInterface;
    /**
     * @var ResultJson|MockObject
     */
    private $resultJson;

    /**
     * @var CustomerInterface|MockObject
     */
    private $getRequest;

    /**
     * @var AbstractAction|MockObject
     */
    private $abstractAction;

    /**
     * @var QueueFactory|MockObject
     */
    private $queueFactory;

    /**
     * @var ResultInterface|MockObject
     */
    private $resultInterface;

    /**
     * @var OrderFactory|MockObject
     */
    private $orderFactory;

    /**
     * @var Data|MockObject
     */
    private $helperData;

    /**
     * @var WebhookRequestProcessor|MockObject
     */
    private $webhookRequestProcessor;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonResultFactory;

    public function testExecute(): void
    {
        $this->abstractAction->expects($this->any())->method('getRequest')->willReturn($this->balanceRequestInterface);
        $this->balanceRequestInterface->expects($this->any())->method('getContent')->willReturn('string');
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->resultInterface);
        $this->resultInterface->expects($this->any())->method('forward')->willReturn($this->requestInterface);
        $this->json->expects($this->any())->method('unserialize')->willReturn([]);
        $this->json->expects($this->any())->method('serialize')->willReturn([]);
        $this->balancepayConfig->expects($this->any())->method('log')->willReturn($this->balancepayConfig);
        $this->queueFactory->expects($this->any())->method('create')->willReturn($this->queue);
        $this->queue->expects($this->any())->method('setData')->willReturn($this->queue);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->resultJson);
        $result = $this->testableObject->execute();
        $this->assertIsObject($result);
    }

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIsAuth',
                'log',
                'isActive'
            ])
            ->getMockForAbstractClass();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode'])
            ->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['updateData'])
            ->getMockForAbstractClass();

        $this->getRequest = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['updateData'])
            ->getMockForAbstractClass();

        $this->abstractAction = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMockForAbstractClass();

        $this->queue = $this->getMockBuilder(Queue::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMockForAbstractClass();

        $this->queueFactory = $this->getMockBuilder(QueueFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setRequestMethod', 'setTopic'])
            ->getMockForAbstractClass();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['unserialize', 'serialize'])
            ->getMockForAbstractClass();

        $this->balanceRequestInterface = $this->getMockBuilder(BalanceRequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getContent'])
            ->getMockForAbstractClass();

        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultInterface = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['forward'])
            ->getMockForAbstractClass();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->webhookRequestProcessor = $this->getMockBuilder(WebhookRequestProcessor::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonResultFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['setData'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(RefundFailed::class, [
            'requestFactory' => $this->requestFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'jsonResultFactory' => $this->jsonResultFactory,
            'json' => $this->json,
            'orderFactory' => $this->orderFactory,
            'helperData' => $this->helperData,
            'webhookRequestProcessor' => $this->webhookRequestProcessor
        ]);
    }
}
