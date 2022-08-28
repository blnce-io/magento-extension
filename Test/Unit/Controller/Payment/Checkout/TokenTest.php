<?php
declare(strict_types=1);
namespace Balancepay\Balancepay\Test\Unit\Controller\Payment\Checkout;

use Balancepay\Balancepay\Model\BalanceBuyer;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Balancepay\Balancepay\Controller\Payment\Checkout\Token;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Forward;
use Balancepay\Balancepay\Model\RequestInterface;
use Balancepay\Balancepay\Model\AbstractResponse;

class TokenTest extends TestCase
{
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonResultFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balancepayConfig = $this->getMockBuilder(BalancepayConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
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

        $this->coreSession = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultInterface = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->abstractResponse = $this->getMockBuilder(AbstractResponse::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->forward = $this->getMockBuilder(Forward::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Token::class, [
            'context' => $this->context,
            'jsonResultFactory' => $this->jsonResultFactory,
            'balancepayConfig' => $this->balancepayConfig,
            'requestFactory' => $this->requestFactory,
            'checkoutSession' => $this->checkoutSession,
            'customerSession' => $this->customerSession,
            'balanceBuyer' => $this->balanceBuyer,
            'coreSession' => $this->coreSession
        ]);
    }

    public function testExecute()
    {
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->resultInterface);
        $this->resultInterface->expects($this->any())->method('forward')->willReturn($this->forward);
        $this->balancepayConfig->expects($this->any())->method('isActive')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setBalanceCustomerEmail')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsBalanceCheckoutToken')->willReturnSelf();
        $this->requestFactory->expects($this->any())->method('create')->willReturn($this->requestInterface);
        $this->requestInterface->expects($this->any())->method('setRequestMethod')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('setFallbackEmail')->willReturnSelf();
        $this->requestInterface->expects($this->any())->method('process')->willReturn($this->abstractResponse);
        $this->abstractResponse->expects($this->any())->method('getToken')->willReturn('string');
        $this->abstractResponse->expects($this->any())->method('getTransactionId')->willReturn('string');
        $this->abstractResponse->expects($this->any())->method('getBuyerId')->willReturn('string');
        $result = $this->testableObject->execute();
    }

}










