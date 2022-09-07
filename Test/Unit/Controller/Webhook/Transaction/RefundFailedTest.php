<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Controller\Webhook\Transaction;

use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundFailed;
use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\QueueFactory;
use Balancepay\Balancepay\Model\Queue;
use Magento\Framework\Controller\Result\JsonFactory;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Balancepay\Balancepay\Model\QueueProcessor;
use Laminas\Http\Headers;
use Magento\Framework\App\Request\Http;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\WebhookRequestProcessor;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\Context;

class RefundFailedTest extends TestCase
{
    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIsAuth',
                'log',
                'isActive',
                'getWebhookSecret'
            ])
            ->getMock();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->chargedProcessor = $this->getMockBuilder(ChargedProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->confirmedProcessor = $this->getMockBuilder(ConfirmedProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->queueProcessor = $this->getMockBuilder(QueueProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->headers = $this->getMockBuilder(Headers::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toArray'])
            ->getMock();

        $context = $this->createMock(Context::class);

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode'])
            ->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['updateData'])
            ->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['updateData'])
            ->getMockForAbstractClass();

        $this->queue = $this->getMockBuilder(Queue::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMockForAbstractClass();

        $this->queueFactory = $this->getMockBuilder(QueueFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['unserialize', 'serialize'])
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

        $context->method('getRequest')
            ->willReturn($this->request);

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(RefundFailed::class, [
            'context' => $context,
            'jsonResultFactory' => $this->jsonResultFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'requestFactory' => $this->requestFactory,
            'json' => $this->json,
            'orderFactory' => $this->orderFactory,
            'helperData' => $this->helperData,
            'webhookRequestProcessor' => $this->webhookRequestProcessor
        ]);

        $this->webhookProcessor = $objectManager->getObject(WebhookRequestProcessor::class, [
            'orderFactory' => $this->orderFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'jsonResultFactory' => $this->jsonResultFactory,
            'chargedProcessor' => $this->chargedProcessor,
            'confirmedProcessor' => $this->confirmedProcessor,
            'queueProcessor' => $this->queueProcessor,
            'json' => $this->json
        ]);
    }

    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    public function testExecute(): void
    {
        $this->request->expects($this->any())->method('getContent')->willReturn('string');
        $this->request->expects($this->any())->method('getHeaders')->willReturn($this->headers);
        $this->headers->expects($this->any())->method('toArray')->willReturn(['test']);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->resultInterface);
        $this->json->expects($this->any())->method('unserialize')->willReturn([]);
        $this->json->expects($this->any())->method('serialize')->willReturn([]);
        $this->webhookProcessor->process('string', ['test'], 'transaction/refund_failed');
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->resultJson);
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('webhooksecretstring');
        $this->queueFactory->expects($this->any())->method('create')->willReturn($this->queue);
        $this->queue->expects($this->any())->method('setData')->willReturn($this->queue);
        $result = $this->testableObject->execute();
        $this->assertIsObject($result);
    }
}
