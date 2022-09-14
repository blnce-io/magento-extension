<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Controller\Webhook\Transaction;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\WebhookRequestProcessor;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Balancepay\Balancepay\Model\QueueProcessor;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Laminas\Http\Headers;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Customer\Api\Data\CustomerInterface;
use Balancepay\Balancepay\Model\Queue;
use Balancepay\Balancepay\Model\QueueFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\AbstractResult;
use Laminas\Crypt\Hmac;
use Balancepay\Balancepay\Controller\Webhook\Transaction\RefundFailed;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use PHPUnit\Framework\TestCase;

class RefundFailedTest extends TestCase
{
    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);

        $this->jsonResultFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['setData'])
            ->getMock();

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
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['unserialize', 'serialize'])
            ->getMock();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->webhookRequestProcessor = $this->getMockBuilder(WebhookRequestProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getRequest')
            ->willReturn($this->request);

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $context->method('getResultFactory')->willReturn($this->resultFactory);

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

        $this->jsonResultFactorySecond = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['setData'])
            ->getMock();

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

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode'])
            ->getMock();

        $this->hmac = $this->getMockBuilder(Hmac::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['compute'])
            ->getMock();

        $this->jsonResultFactorySecond->expects($this->any())->method('create')->willReturn($this->resultJson);

        $this->webhookProcessor = $objectManager->getObject(WebhookRequestProcessor::class, [
            'orderFactory' => $this->orderFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'jsonResultFactory' => $this->jsonResultFactorySecond,
            'chargedProcessor' => $this->chargedProcessor,
            'confirmedProcessor' => $this->confirmedProcessor,
            'queueProcessor' => $this->queueProcessor,
            'json' => $this->json,
            'hmac' => $this->hmac
        ]);

        $this->headers = $this->getMockBuilder(Headers::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toArray'])
            ->getMock();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['updateData'])
            ->getMockForAbstractClass();

        $this->queue = $this->getMockBuilder(Queue::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMock();

        $this->queueFactory = $this->getMockBuilder(QueueFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->resultInterface = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['forward'])
            ->getMockForAbstractClass();

        $this->abstractResult = $this->getMockBuilder(AbstractResult::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();
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
        $this->headers->expects($this->any())->method('toArray')->willReturn(['X-Blnce-Signature'=>'balancesignature']);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->resultInterface);
        $this->json->expects($this->any())->method('unserialize')->willReturn([]);
        $this->json->expects($this->any())->method('serialize')->willReturn([]);
        $this->webhookProcessor->process('string', ['test'], 'transaction/refund_failed');
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('webhooksecretstring');
        $this->hmac->expects($this->any())->method('compute')->willReturn('balancesignature');
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $this->queueFactory->expects($this->any())->method('create')->willReturn($this->queue);
        $this->queue->expects($this->any())->method('setData')->willReturn($this->queue);
        $result = $this->testableObject->execute();
    }

    public function testExecuteNotActive()
    {
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(false);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->resultInterface);
        $this->resultInterface->expects($this->any())->method('forward')->willReturnSelf();
        $result = $this->testableObject->execute();
    }

    public function testCreateCsrfValidationException()
    {
        $result = $this->testableObject->createCsrfValidationException($this->requestInterface);
    }

    public function testValidateForCsrf()
    {
        $result = $this->testableObject->validateForCsrf($this->requestInterface);
    }
}
