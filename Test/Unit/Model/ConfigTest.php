<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\Config;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConfigTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->resourceConfig = $this->getMockBuilder(ResourceConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->encryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTime = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Config::class, [
            'scopeConfig' => $this->scopeConfig,
            'resourceConfig' => $this->resourceConfig,
            'storeManager' => $this->storeManager,
            'encryptor' => $this->encryptor,
            'logger' => $this->logger,
            'urlBuilder' => $this->urlBuilder,
            'dateTime' => $this->dateTime,
        ]);
    }

    public function testUpdateBalancePayStatus(): void
    {
        $this->resourceConfig->method('saveConfig')->willReturnSelf();
        $result = $this->testableObject->updateBalancePayStatus();
        $this->assertNull($result);
    }

    public function testIsActive(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->isActive();
        $this->assertIsBool($result);
    }

    public function testGetTitle(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getTitle();
        $this->assertNull($result);
    }

    public function testGetIsAuth(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getIsAuth();
        $this->assertIsBool($result);
    }

    public function testGetLogoImageUrl(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getLogoImageUrl();
        $this->assertIsString($result);
    }

    public function testIsSandboxMode(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->isSandboxMode();
        $this->assertIsBool($result);
    }

    public function testGetAllowedPaymentMethods(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getAllowedPaymentMethods();
        $this->assertIsArray($result);
    }

    public function testIsDebugEnabled(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->isDebugEnabled();
        $this->assertIsBool($result);
    }

    public function testGetAllowedTermsPaymentMethods(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getAllowedTermsPaymentMethods();
        $this->assertIsArray($result);
    }

    public function testGetBalanceSdkUrl(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getBalanceSdkUrl();
        $this->assertIsString($result);
    }

    public function testGetBalanceIframeUrl(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getBalanceIframeUrl();
        $this->assertIsString($result);
    }

    public function testGetBalanceApiUrl(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getBalanceApiUrl();
        $this->assertIsString($result);
    }

    public function testGetAllowedCustomerGroups(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->getAllowedCustomerGroups();
        $this->assertIsArray($result);
    }

    public function testGetGmtDate(): void
    {
        $this->dateTime->method('gmtDate')->willReturn('1/1/22');
        $result = $this->testableObject->getGmtDate();
        $this->assertIsString($result);
    }

    public function testGetStoreManager()
    {
        $result = $this->testableObject->getStoreManager();
    }

    public function testGetUrlBuilder()
    {
        $result = $this->testableObject->getUrlBuilder();
    }

    public function testGetApiKey(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $this->encryptor->method('decrypt')->willReturn('string');
        $result = $this->testableObject->getApiKey();
        $this->assertNull($result);
    }

    public function testGetMerchantTermsOptions(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $this->encryptor->method('decrypt')->willReturn('string');
        $result = $this->testableObject->getMerchantTermsOptions();
        $this->assertIsArray($result);
    }

    public function testGetReservedOrderId(): void
    {
        $this->quote->method('getReservedOrderId')->willReturn('string');
        $result = $this->testableObject->getReservedOrderId($this->quote);
        $this->assertIsString($result);
    }

    public function testLog(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $this->logger->method('error');
        $this->logger->method('info');
        $this->logger->method('debug');
        $result = $this->testableObject->log('saved', 'debug', [], '[Balancepay]');
        $this->assertIsObject($result);
    }

    public function testGetWebhookSecret(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $this->encryptor->method('decrypt')->willReturn('string');
        $result = $this->testableObject->getWebhookSecret();
        $this->assertNull($result);
    }

    public function testGetCurrentStoreId(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn(556557);
        $result = $this->testableObject->getCurrentStoreId();
        $this->assertIsInt($result);
    }

    public function testGetCurrentStore(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $result = $this->testableObject->getCurrentStore();
        $this->assertIsObject($result);
    }

    public function testIsSingleStoreMode(): void
    {
        $this->storeManager->method('isSingleStoreMode')->willReturn(true);
        $result = $this->testableObject->isSingleStoreMode();
        $this->assertIsBool($result);
    }

    public function testUpdateWebhookSecret(): void
    {
        $this->resourceConfig->method('saveConfig')->willReturnSelf();
        $this->encryptor->method('encrypt')->willReturn('string');
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->updateWebhookSecret();
        $this->assertIsObject($result);
    }

    public function testResetStoreCredentials(): void
    {
        $this->resourceConfig->method('deleteConfig')->willReturnSelf();
        $this->storeManager->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->method('getId')->willReturn('556557');
        $result = $this->testableObject->resetStoreCredentials();
        $this->assertIsObject($result);
    }
}
