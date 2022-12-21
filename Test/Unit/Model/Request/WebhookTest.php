<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model\Request;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Webhooks;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Store\Api\Data\StoreInterface;

class WebhookTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Curl|\PHPUnit\Framework\MockObject\MockObject
     */
    private $curl;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $balancepayConfig;

    /**
     * @var ResponseFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactory;

    /**
     * @var HelperData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helper;

    /**
     * @var AccountManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $accountManagement;

    /**
     * @var RegionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $region;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $request;

    /**
     * @var StoreInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeInterface;

    public function testSetTopic()
    {
        $result = $this->testableObject->setTopic('topic');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testSetWebookAddress()
    {
        $result = $this->testableObject->setWebookAddress('webhookCharge');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testGetTopic()
    {
        $result = $this->testableObject->getTopic();
    }

    public function testGetWebookAddress()
    {
        $result = $this->testableObject->getWebookAddress();
    }

    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()->getMock();

        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->helper = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()->getMock();

        $this->accountManagement = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->region = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()->addMethods(['getBaseUrl'])->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Webhooks::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'curl' => $this->curl,
            'responseFactory' => $this->responseFactory,
            'helper' => $this->helper,
            'accountManagement' => $this->accountManagement,
            'region' => $this->region,
            'request' => $this->request
        ]);
    }
}
