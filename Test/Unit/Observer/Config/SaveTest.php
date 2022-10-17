<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Observer\Config;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\RequestInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Observer\Config\Save;
use Magento\Store\Model\StoreManagerInterface;

class SaveTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(BalancepayConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getApiKey', 'isActive', 'resetStoreCredentials', 'updateBalancePayStatus', 'getStoreManager'])
            ->getMock();

        $this->reinitableConfigInterface = $this->getMockBuilder(ReinitableConfigInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();

        $this->typeListInterface = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();

        $this->messageManagerInterface = $this->getMockBuilder(MessageManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMockForAbstractClass();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getDefaultStore'])->onlyMethods(['getWebsite'])
            ->getMockForAbstractClass();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->appEmulation = $this->getMockBuilder(AppEmulation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['stopEnvironmentEmulation', 'startEnvironmentEmulation'])
            ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStore', 'getWebsite'])
            ->getMock();

        $this->emulation = $this->getMockBuilder(AppEmulation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['stopEnvironmentEmulation'])
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['update', 'setTopic', 'setWebookAddress'])->onlyMethods(['process'])
            ->getMockForAbstractClass();

        $this->messageManagerInterface = $this->getMockBuilder(MessageManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])->getMockForAbstractClass();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])->getMock();

        $this->websiteInterface = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getDefaultStore'])->getMockForAbstractClass();


        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Save::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'appConfig' => $this->reinitableConfigInterface,
            'cacheTypeList' => $this->typeListInterface,
            'messageManager' => $this->messageManagerInterface,
            'requestFactory' => $this->requestFactory,
            'appEmulation' => $this->appEmulation
        ]);
    }

    public function testExecute()
    {
        $this->typeListInterface->expects($this->any())->method('cleanType')->with(Config::TYPE_IDENTIFIER)->willReturn('null');
        $this->reinitableConfigInterface->expects($this->any())->method('reinit')->willReturnSelf();
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getStore')->willReturn(1);
        $this->balancepayConfig->expects($this->any())->method('getApiKey')->willReturn('wertyuiop');
        $this->appEmulation->expects($this->any())->method('stopEnvironmentEmulation')->willReturn($this->emulation);
        $this->appEmulation->expects($this->any())->method('startEnvironmentEmulation')->willReturn(null);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(1);
        $this->requestFactory->expects($this->any())->method('create')
            ->withConsecutive(['webhooks/keys'],['webhooks'])->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('process')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('update')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setWebookAddress')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }

    public function testExecuteNoStore()
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->typeListInterface->expects($this->any())->method('cleanType')->with(Config::TYPE_IDENTIFIER)->willReturn('null');
        $this->reinitableConfigInterface->expects($this->any())->method('reinit')->willReturnSelf();
        $this->event->expects($this->any())->method('getStore')->willReturn(0);
        $this->event->expects($this->any())->method('getWebsite')->willReturn(2);
        $this->balancepayConfig->expects($this->any())->method('getApiKey')->willReturn('wertyuiop');
        $this->appEmulation->expects($this->any())->method('stopEnvironmentEmulation')->willReturn($this->emulation);
        $this->appEmulation->expects($this->any())->method('startEnvironmentEmulation')->willReturn(null);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(1);
        $this->balancepayConfig->expects($this->any())->method('getStoreManager')->willReturn($this->storeManagerInterface);
        $this->storeManagerInterface->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getDefaultStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getId')->willReturn(1);
        $this->requestFactory->expects($this->any())->method('create')
            ->withConsecutive(['webhooks/keys'],['webhooks'])->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('process')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('update')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setWebookAddress')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }

    public function testExecuteNoStoreNoWebsite()
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->typeListInterface->expects($this->any())->method('cleanType')->with(Config::TYPE_IDENTIFIER)->willReturn('null');
        $this->reinitableConfigInterface->expects($this->any())->method('reinit')->willReturnSelf();
        $this->event->expects($this->any())->method('getStore')->willReturn(0);
        $this->event->expects($this->any())->method('getWebsite')->willReturn(0);
        $this->balancepayConfig->expects($this->any())->method('getApiKey')->willReturn('wertyuiop');
        $this->appEmulation->expects($this->any())->method('stopEnvironmentEmulation')->willReturn($this->emulation);
        $this->appEmulation->expects($this->any())->method('startEnvironmentEmulation')->willReturn(null);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(1);
        $this->balancepayConfig->expects($this->any())->method('getStoreManager')->willReturn($this->storeManagerInterface);
        $this->storeManagerInterface->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getDefaultStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getId')->willReturn(1);
        $this->requestFactory->expects($this->any())->method('create')
            ->withConsecutive(['webhooks/keys'],['webhooks'])->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('process')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('update')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setWebookAddress')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }

    public function testExecuteThrowsException()
    {
        $this->typeListInterface->expects($this->any())->method('cleanType')->with(Config::TYPE_IDENTIFIER)->willReturn('null');
        $this->reinitableConfigInterface->expects($this->any())->method('reinit')->willReturnSelf();
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getStore')->willReturn(1);
        $this->event->expects($this->any())->method('getWebsite')->willReturn(2);
        $this->balancepayConfig->expects($this->any())->method('getApiKey')->willReturn('wertyuiop');
        $this->appEmulation->expects($this->any())->method('stopEnvironmentEmulation')->willReturn($this->emulation);
        $this->appEmulation->expects($this->any())->method('startEnvironmentEmulation')->willReturn(null);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(1);
        $this->requestFactory->expects($this->any())->method('create')
            ->withConsecutive(['webhooks/keys'],['webhooks'])->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('process')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('update')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setWebookAddress')->willThrowException(new \Exception());
        $this->balancepayConfig->expects($this->any())->method('updateBalancePayStatus')->willReturn(null);
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }

    /**
     * @throws LocalizedException
     */
    public function testExecuteNoApiKey()
    {
        $this->typeListInterface->expects($this->any())->method('cleanType')->with(Config::TYPE_IDENTIFIER)->willReturn('null');
        $this->reinitableConfigInterface->expects($this->any())->method('reinit')->willReturnSelf();
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getStore')->willReturn(1);
        $this->event->expects($this->any())->method('getWebsite')->willReturn(2);
        $this->balancepayConfig->expects($this->any())->method('getApiKey')->willReturn('');
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(1);
        $this->appEmulation->expects($this->any())->method('stopEnvironmentEmulation')->willReturn($this->emulation);
        $this->balancepayConfig->expects($this->any())->method('resetStoreCredentials')->willReturnSelf();
        $this->expectException(LocalizedException::class);
        $result = $this->testableObject->execute($this->observer);
        $this->assertNull($result);
    }
}













