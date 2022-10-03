<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Model\WebhookRequestProcessor;
use Laminas\Crypt\Hmac;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Balancepay\Balancepay\Model\QueueProcessor;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Balancepay\Balancepay\Model\Queue;
use Balancepay\Balancepay\Model\QueueFactory;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookRequestProcessorTest extends TestCase
{
    /**
     * @var JsonFactory|MockObject
     */
    private $jsonResultFactory;

    /**
     * @var Config|MockObject
     */
    private $balancepayConfig;

    /**
     * @var Json|MockObject
     */
    private $json;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var ChargedProcessor|MockObject
     */
    private $chargedProcessor;

    /**
     * @var ConfirmedProcessor|MockObject
     */
    private $confirmedProcessor;

    /**
     * @var QueueProcessor|MockObject
     */
    private $queueProcessor;

    /**
     * @var Queue|MockObject
     */
    private $queue;

    /**
     * @var ResultJson|MockObject
     */
    private $resultJson;

    /**
     * @var QueueFactory|MockObject
     */
    private $queueFactory;

    /**
     * @var AbstractResult|MockObject
     */
    private $abstractResult;

    protected function setUp(): void
    {
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

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['unserialize', 'serialize'])
            ->getMock();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
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

        $this->queue = $this->getMockBuilder(Queue::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMock();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode'])
            ->getMock();

        $this->queueFactory = $this->getMockBuilder(QueueFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->abstractResult = $this->getMockBuilder(AbstractResult::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(WebhookRequestProcessor::class, [
            'jsonResultFactory' => $this->jsonResultFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'json' => $this->json,
            'orderFactory' => $this->orderFactory,
            'chargedProcessor' => $this->chargedProcessor,
            'confirmedProcessor' => $this->confirmedProcessor,
            'queueProcessor' => $this->queueProcessor
        ]);
    }

    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    public function testProcess(): void
    {
        $this->json->expects($this->any())->method('unserialize')->willReturn([]);
        $this->json->expects($this->any())->method('serialize')->willReturn([]);
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->queueFactory->expects($this->any())->method('create')->willReturn($this->queue);
        $this->queue->expects($this->any())->method('setData')->willReturn($this->queue);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $result = $this->testableObject->process([], [], 'webhook');
    }

    public function testValidateSignature(): void
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn([]);
        $result = $this->testableObject->validateSignature([], [], 'webhook');
    }

}
