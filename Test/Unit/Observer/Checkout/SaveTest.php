<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Observer\Checkout;

use Balancepay\Balancepay\Model\AbstractResponse;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Request\Webhooks;
use Balancepay\Balancepay\Model\RequestInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Balancepay\Balancepay\Observer\Config\Save;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Config|MockObject
     */
    private $balancepayConfig;

    /**
     * @var AuthorizeCommand|MockObject
     */
    private $authorizeCommand;

    /**
     * @var InvoiceService|MockObject
     */
    private $invoiceService;

    /**
     * @var CaptureCommand|MockObject
     */
    private $captureCommand;

    /**
     * @var TransactionFactory|MockObject
     */
    private $transactionFactory;

    /**
     * @var Phrase|MockObject
     */
    private $phrase;

    /**
     * @var OrderPaymentInterface|MockObject
     */
    private $orderpaymentinterface;

    /**
     * @var LocalizedException|MockObject
     */
    private $localizedException;

    public function testExecute(): void
    {
        $this->cacheTypeList->expects($this->any())->method('cleanType');
        $this->appConfig->expects($this->any())->method('reinit')->willReturn($this->appConfig);
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getStore')->willReturn('balance');
        $this->event->expects($this->any())->method('getWebsite')->willReturn('balancepay');
        $this->balancepayConfig->expects($this->any())->method('getApiKey')->willReturn('string');
        $this->appEmulation->expects($this->any())->method('stopEnvironmentEmulation')->willReturn($this->appEmulation);
        $this->appEmulation->expects($this->any())->method('startEnvironmentEmulation');
        $this->balancepayConfig->expects($this->any())
            ->method('getStoreManager')->willReturn($this->storeManagerInterface);
        $this->storeManagerInterface->expects($this->any())
            ->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())
            ->method('getDefaultStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getId')->willReturn(1);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->requestInterface);
        $this->balancepayConfig->expects($this->any())->method('getBalanceApiUrl');
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('setWebookAddress')->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('process')->willReturn($this->abstractResponse);
        $this->messageManager->expects($this->any())->method('addSuccess')->willReturn($this->messageManager);
        $this->balancepayConfig->expects($this->any())->method('updateBalancePayStatus');
        $this->balancepayConfig->expects($this->any())->method('resetStoreCredentials')->willReturnSelf();
        $result = $this->testableObject->execute($this->observer);
        $this->assertIsObject($result);
    }

    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getApiKey',
                'getStoreManager',
                'isActive',
                'getBalanceApiUrl',
                'updateBalancePayStatus',
                'resetStoreCredentials'])
            ->getMockForAbstractClass();

        $this->appConfig = $this->getMockBuilder(ReinitableConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheTypeList = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractResponse = $this->getMockBuilder(AbstractResponse::class)
            ->disableOriginalConstructor()
            ->addMethods(['update'])
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTopic', 'setFallbackEmail', 'setWebookAddress'])
            ->getMockForAbstractClass();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->webhooks = $this->getMockBuilder(Webhooks::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setWebookAddress'])
            ->getMockForAbstractClass();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMockForAbstractClass();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStore', 'getWebsite'])
            ->getMock();

        $this->messageManager = $this->getMockBuilder(MessageManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterface = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getDefaultStore'])
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getPayment',
                'getBillingAddress',
                'getBaseGrandTotal',
                'save'
            ])->addMethods([
                'getMethod'
            ])
            ->getMock();

        $this->appEmulation = $this->getMockBuilder(AppEmulation::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'execute'
            ])->onlyMethods([
                'stopEnvironmentEmulation',
                'startEnvironmentEmulation'
            ])
            ->getMockForAbstractClass();

        $this->phrase = $this->getMockBuilder(Phrase::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->exception = $this->getMockBuilder(\Exception::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->invoiceService = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->transactionFactory = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Save::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'appConfig' => $this->appConfig,
            'cacheTypeList' => $this->cacheTypeList,
            'messageManager' => $this->messageManager,
            'requestFactory' => $this->requestFactory,
            'appEmulation' => $this->appEmulation
        ]);
    }
}
