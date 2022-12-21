<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\AbstractResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Customer\Model\Session;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;

class BalancepayMethodTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;
    /**
     * @var RequestFactory|MockObject
     */
    private $requestFactory;
    /**
     * @var HelperData|MockObject
     */
    private $helperData;
    /**
     * @var Session|MockObject
     */
    private $session;
    /**
     * @var CartInterface|MockObject
     */
    private $cartInterface;
    /**
     * @var Order|MockObject
     */
    private $order;
    /**
     * @var OrderItemInterface|MockObject
     */
    private $orderItemInterface;
    /**
     * @var InfoInterface|MockObject
     */
    private $infoInterface;
    /**
     * @var \Balancepay\Balancepay\Model\RequestInterface|MockObject
     */
    private $requestInterface;
    /**
     * @var AbstractResponse|MockObject
     */
    private $abstractResponse;
    /**
     * @var Config|MockObject
     */
    private $config;
    /**
     * @var Registry|MockObject
     */
    private $registry;
    /**
     * @var AbstractMethod|MockObject
     */
    private $abstractMethod;
    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])->getMock();

        $this->helperData = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()->addMethods(['getOrder'])->getMock();

        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()->addMethods([])->getMock();

        $this->orderItemInterface = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->_eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->requestInterface = $this->getMockBuilder(\Balancepay\Balancepay\Model\RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setRequestMethod', 'setTopic', 'setPayment'])->onlyMethods(['process'])
            ->getMockForAbstractClass();

        $this->cartInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['isMultipleShippingAddresses'])
            ->getMockForAbstractClass();

        $this->infoInterface = $this->getMockBuilder(InfoInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsTransactionPending', 'getOrder'])
            ->getMockForAbstractClass();

        $this->abstractResponse = $this->getMockBuilder(AbstractResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();

        $this->abstractMethod = $this->getMockBuilder(AbstractMethod::class)
            ->disableOriginalConstructor()->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(BalancepayMethod::class, [
            'requestFactory' => $this->requestFactory,
            'helper' => $this->helperData,
            'request' => $this->request,
            'registry' => $this->registry,
            'customerSession' => $this->session,
            'checkoutSession' => $this->checkoutSession,
            'balancepayConfig' => $this->config
        ]);
    }

    public function testIsAvailable()
    {
        $this->cartInterface->expects($this->any())->method('isMultipleShippingAddresses')->willReturn(true);
        $this->helperData->expects($this->any())->method('isCustomerGroupAllowed')->willReturn(true);
        $result = $this->testableObject->isAvailable($this->cartInterface);
        $this->assertIsBool($result);
    }

    public function testIsAvailableFalse()
    {
        $this->cartInterface->expects($this->any())->method('isMultipleShippingAddresses')->willReturn(false);
        $this->helperData->expects($this->any())->method('isCustomerGroupAllowed')->willReturn(true);
        $result = $this->testableObject->isAvailable($this->cartInterface);
        $this->assertIsBool($result);
    }

    public function testAssignData()
    {
        $this->_eventManager->expects($this->any())->method('dispatch')->willReturn(null);
        $this->expectException(LocalizedException::class);
        $result = $this->testableObject->assignData($this->dataObject);
    }

    public function testGetConfigPaymentAction()
    {
        $this->config->expects($this->any())->method('getIsAuth')->willReturn(true);
        $result = $this->testableObject->getConfigPaymentAction();
        $this->assertIsString($result);
    }

    public function testOrder()
    {
        $this->infoInterface->expects($this->any())->method('setAdditionalInformation')->willReturn($this->infoInterface);
        $this->infoInterface->expects($this->any())->method('setIsTransactionPending')->willReturn($this->infoInterface);
        $result = $this->testableObject->order($this->infoInterface, 4);
    }

    public function testAuthorize()
    {
        $this->infoInterface->expects($this->any())->method('setAdditionalInformation')->willReturn($this->infoInterface);
        $result = $this->testableObject->authorize($this->infoInterface, 4);
    }

    public function testCapture()
    {
        $this->infoInterface->expects($this->any())->method('getAdditionalInformation')->willReturn([]);
        $this->request->expects($this->any())->method('getParam');
        $this->infoInterface->expects($this->any())->method('getOrder')->willReturn($this->order);
        $this->order->expects($this->any())->method('getOrder')->willReturn($this->orderItemInterface);
        $this->helperData->expects($this->any())->method('getBalanceVendors')->willReturn('string');
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('setPayment')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setTopic')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('process')->willReturn($this->abstractResponse);
        $this->registry->expects($this->any())->method('register');

        $result = $this->testableObject->capture($this->infoInterface, 4);
    }

    public function testCancel()
    {
        $this->infoInterface->expects($this->any())->method('getAdditionalInformation')->willReturn([]);
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('setPayment')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('process')->willReturn($this->abstractResponse);
        $result = $this->testableObject->cancel($this->infoInterface);
    }
}
