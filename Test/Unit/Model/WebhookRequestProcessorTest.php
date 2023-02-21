<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Controller\Webhook\Transaction\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Model\ChargedProcessor;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Balancepay\Balancepay\Model\Queue;
use Balancepay\Balancepay\Model\QueueFactory;
use Balancepay\Balancepay\Model\QueueProcessor;
use Balancepay\Balancepay\Model\WebhookRequestProcessor;
use Laminas\Crypt\Hmac;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\OrderFactory;
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
            ->onlyMethods(['addToQueue'])
            ->getMock();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode'])
            ->getMock();

        $this->hmac = $this->getMockBuilder(Hmac::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['compute'])
            ->getMock();

        $this->abstractResult = $this->getMockBuilder(AbstractResult::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(WebhookRequestProcessor::class, [
            'orderFactory' => $this->orderFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'jsonResultFactory' => $this->jsonResultFactory,
            'chargedProcessor' => $this->chargedProcessor,
            'confirmedProcessor' => $this->confirmedProcessor,
            'queueProcessor' => $this->queueProcessor,
            'json' => $this->json,
            'hmac' => $this->hmac
        ]);
    }

    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    public function testProcessConfirmedWebhook()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn(
            [
                'externalReferenceId' => '1000023',
                'isFinanced' => true,
                'selectedPaymentMethod' => 'tdt4424234304b'
            ]
        );
        $this->queueProcessor->expects($this->any())->method('addToQueue')->willReturn(null);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => '7bd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, Confirmed::WEBHOOK_CONFIRMED_NAME);
    }

    public function testProcessChargedWebhook()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn(
            [
                'externalReferenceId' => '1000023',
                'chargeId' => '12345',
                'amount' => '20'
            ]
        );
        $this->queueProcessor->expects($this->any())->method('addToQueue')->willReturn(null);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => '7bd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, Charged::WEBHOOK_CHARGED_NAME);
    }

    public function testProcessRefundWebhookSuccess()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn(
            [
                'externalReferenceId' => '1000023',
                'selectedPaymentMethod' => 'tersdf0445340v',
                'status' => 'pending'
            ]
        );
        $this->queueProcessor->expects($this->any())->method('addToQueue')->willReturn(null);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => '7bd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, 'transaction/refund_successful');
    }

    public function testProcessRefundWebhookFailed()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn(
            [
                'externalReferenceId' => '1000023',
                'selectedPaymentMethod' => 'tersdf0445340v',
                'status' => 'pending'
            ]
        );
        $this->queueProcessor->expects($this->any())->method('addToQueue')->willReturn(null);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => '7bd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, 'transaction/refund_failed');
    }

    public function testProcessRefundWebhookCanceled()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn(
            [
                'externalReferenceId' => '1000023',
                'selectedPaymentMethod' => 'tersdf0445340v',
                'status' => 'pending'
            ]
        );
        $this->queueProcessor->expects($this->any())->method('addToQueue')->willReturn(null);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => '7bd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, 'transaction/refund_canceled');
    }

    public function testProcessRefundWebhookDiffKeys()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->json->expects($this->any())->method('unserialize')->willReturn(
            [
                'externalReferenceId' => '1000023',
                'status' => 'pending'
            ]
        );
        $this->queueProcessor->expects($this->any())->method('addToQueue')->willReturn(null);
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => '7bd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, 'transaction/refund_successful');
    }

    public function testProcessSignatureNoMatch()
    {
        $this->balancepayConfig->expects($this->any())->method('getWebhookSecret')->willReturn('string');
        $this->jsonResultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setHttpResponseCode')->willReturn($this->abstractResult);
        $headers = [
            'X-Blnce-Signature' => 'd24c445433123c4ac69885dbded509657f4cda82f5c2401cf036e5e6aa7583'
        ];
        $result = $this->testableObject->process('contentstring', $headers, 'transaction/refund_successful');
    }
}
