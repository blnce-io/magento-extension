<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Model\ConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Payment\Model\MethodInterface;

class ConfigProviderTest extends TestCase
{
    /**
     * @var object
     */
    private $testableObject;

    /**
     * @return void
     */
    public function testGetConfig()
    {
        $this->config->expects($this->any())->method('isActive')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('unsBalanceCustomerEmail')
            ->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsBalanceCheckoutToken')
            ->willReturnSelf();
        $this->config->expects($this->any())->method('getBalanceSdkUrl')
            ->willReturn('https://balancesdkurl.com');
        $this->urlInterface->expects($this->any())->method('getUrl')
            ->willReturn('https://balancetokenurl.com');
        $this->config->expects($this->any())->method('getBalanceIframeUrl')
            ->willReturn('https://balanceiframeurl.com');
        $this->config->expects($this->any())->method('getLogoImageUrl')
            ->willReturn('https://balancelogoimageurl.com');
        $this->config->expects($this->any())->method('getIsAuth')
            ->willReturn(true);

        $result = $this->testableObject->getConfig();
    }

    public function testGetConfigFalse()
    {
        $this->config->expects($this->any())->method('isActive')->willReturn(false);
        $result = $this->testableObject->getConfig();
    }

    protected function setUp(): void
    {
        $this->ccConfig = $this->getMockBuilder(CcConfig::class)
            ->disableOriginalConstructor()
            ->addMethods(['getValue'])->getMock();

        $this->paymentHelper = $this->getMockBuilder(PaymentHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethodInstance'])->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])->getMockForAbstractClass();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isActive',
                'getBalanceSdkUrl',
                'getBalanceIframeUrl',
                'getLogoImageUrl',
                'getIsAuth',
                'getUrlBuilder'
            ])->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['unsBalanceCustomerEmail', 'unsBalanceCheckoutToken'])->getMock();

        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMockForAbstractClass();

        $this->methodInterface = $this->getMockBuilder(MethodInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMockForAbstractClass();

        $this->paymentHelper->expects($this->any())->method('getMethodInstance')
            ->willReturn($this->methodInterface);

        $this->ccConfig->expects($this->any())->method('getValue')
            ->willReturn('https://balancelogoimageurl.com');

        $this->config->expects($this->any())->method('getUrlBuilder')
            ->willReturn($this->urlInterface);

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(ConfigProvider::class, [
            'ccConfig' => $this->ccConfig,
            'paymentHelper' => $this->paymentHelper,
            'balancepayConfig' => $this->config,
            'checkoutSession' => $this->checkoutSession,
            'methodCodes' => ['balancepay'],
        ]);
    }
}
