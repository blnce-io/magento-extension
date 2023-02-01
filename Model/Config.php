<?php
/**
 * Balance Payments For Magento 2
 * https://www.getbalance.com/
 *
 * @category Balance
 * @package  Balancepay_Balancepay
 * @author   Developer: Pniel Cohen
 * @author   Company: Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Balancepay\Balancepay\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Balancepay config model.
 */
class Config
{
    public const MODULE_NAME = 'Balancepay_Balancepay';

    public const BALANCEPAY_SDK_SANDBOX_URL = 'https://checkout-v2.sandbox.getbalance.com/sdk-latest.js'; //Sandbox
    public const BALANCEPAY_SDK_LIVE_URL = 'https://checkout-v2.getbalance.com/sdk-latest.js'; //Production
    public const BALANCEPAY_API_SANDBOX_URL = 'https://sandbox.app.blnce.io/api/v1/'; //Sandbox
    public const BALANCEPAY_API_LIVE_URL = 'https://app.blnce.io/api/v1/'; //Production
    public const BALANCEPAY_IFRAME_SANDBOX_URL = 'https://checkout-v2.sandbox.getbalance.com/checkout.html'; //Sandbox
    public const BALANCEPAY_IFRAME_LIVE_URL = 'https://checkout-v2.getbalance.com/checkout.html'; //Production

    // Developer config keys
    public const CONFIG_KEY_USE_DEV_ENV = 'use_dev_env';
    public const CONFIG_KEY_DEV_API_KEY = 'dev_api_key';
    public const CONFIG_KEY_DEV_SDK_URL = 'dev_sdk_url';
    public const CONFIG_KEY_DEV_API_URL = 'dev_api_url';
    public const CONFIG_KEY_DEV_IFRAME_URL = 'dev_iframe_url';
    public const CONFIG_KEY_BALANCE_WEBHOOKS_BASE_URL = 'balance_webhooks_base_url';

    /**
     * Scope config object.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * Store manager object.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @method __construct
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConfig $resourceConfig
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     * @param DateTime $dateTime
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        UrlInterface $urlBuilder,
        DateTime $dateTime
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->dateTime = $dateTime;
    }

    /**
     * Update Balance Pay status
     *
     * @param string $scope
     * @param int $storeId
     */
    public function updateBalancePayStatus($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->resourceConfig->saveConfig(
            $this->getConfigPath() . 'active',
            0,
            $scope,
            $storeId
        );
    }

    /**
     * Return config path.
     *
     * @return string
     */
    private function getConfigPath()
    {
        return sprintf('payment/%s/', BalancepayMethod::METHOD_CODE);
    }

    /**
     * Return store manager.
     *
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Return URL Builder
     *
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * Return GMT Date
     */
    public function getGmtDate()
    {
        return $this->dateTime->gmtDate();
    }

    /**
     * UpdateWebhookSecret
     *
     * @param string $webhookSecret
     * @param string $scope
     * @param int $storeId
     * @return $this
     */
    public function updateWebhookSecret($webhookSecret = "", $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->resourceConfig->saveConfig(
            $this->getConfigPath() .
            ($this->isSandboxMode($scope, $storeId) ? 'sandbox_webhook_secret' : 'webhook_secret'),
            $this->encryptor->encrypt($webhookSecret),
            $scope,
            $storeId
        );
        return $this;
    }

    /**
     * Is sandbox mode
     *
     * @param string $scope
     * @param int $storeId
     * @return bool
     */
    public function isSandboxMode($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->getConfigValue('mode', $scope, $storeId) === BalancepayMethod::MODE_LIVE) ? false : true;
    }

    /**
     * GetConfigValue
     *
     * @param string $fieldKey
     * @param string $scope
     * @param int $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getConfigValue($fieldKey, $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($this->getConfigPath() . $fieldKey);
        }
        return $this->scopeConfig->getValue(
            $this->getConfigPath() . $fieldKey,
            $scope ?: ScopeInterface::SCOPE_STORE,
            ($storeId == null) ? $this->getCurrentStoreId() : $storeId
        );
    }

    /**
     * IsSingleStoreMode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * GetCurrentStoreId
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * ResetStoreCredentials
     *
     * @param string $scope
     * @param int $storeId
     * @return $this
     */
    public function resetStoreCredentials($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->resourceConfig->deleteConfig($this->getConfigPath() . 'active', $scope, $storeId);
        $this->resourceConfig->deleteConfig($this->getConfigPath() .
            ($this->isSandboxMode($scope, $storeId) ? 'sandbox_api_key' : 'api_key'), $scope, $storeId);
        return $this;
    }

    /**
     * Return bool value depends of that if payment method is active or not
     *
     * @param string $scope
     * @param int $storeId
     * @return bool
     */
    public function isActive($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue('active', $scope, $storeId);
    }

    /**
     * Return title.
     *
     * @param string $scope
     * @param int $storeId
     * @return mixed
     */
    public function getTitle($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->getConfigValue('title', $scope, $storeId);
    }

    /**
     * GetIsAuth
     *
     * @param string $scope
     * @param int $storeId
     * @return bool
     */
    public function getIsAuth($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue('is_auth', $scope, $storeId);
    }

    /**
     * GetLogoImageUrl
     *
     * @param string $scope
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLogoImageUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        if (!($logoImage = $this->getConfigValue('logo_image', $scope, $storeId))) {
            return '';
        }
        return $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'balancepay/' . $logoImage;
    }

    /**
     * Get api key
     *
     * @param string $scope
     * @param int $storeId
     * @return string|null
     */
    public function getApiKey($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $configKey = $this->getConfigValue(
            $this->resolveFromEnvironment(
                'api_key',
                'sandbox_api_key',
                self::CONFIG_KEY_DEV_API_KEY
            ),
            $scope,
            $storeId
        );
        return (($val = $configKey)) ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * ResolveFromEnvironment
     *
     * @param string|int|mixed|array $liveValue
     * @param string|int|mixed|array $sandboxValue
     * @param string|int|mixed|array $devValue
     * @param string $scope
     * @param string|int|mixed|array $storeId
     * @return mixed
     */
    private function resolveFromEnvironment(
        $liveValue,
        $sandboxValue,
        $devValue,
        $scope = ScopeInterface::SCOPE_STORE,
        $storeId = null
    ) {
        if ($this->getUseDevEnv($scope, $storeId)) {
            return $devValue;
        }
        return ($this->isSandboxMode($scope, $storeId)
            ? $sandboxValue : $liveValue);
    }

    /**
     * Whether to use development environment
     *
     * @param string $scope
     * @param int $storeId
     * @return bool
     */
    public function getUseDevEnv($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue(self::CONFIG_KEY_USE_DEV_ENV, $scope, $storeId);
    }

    /**
     * Get webhook secret
     *
     * @param string $scope
     * @param int $storeId
     * @return string|null
     */
    public function getWebhookSecret($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode($scope, $storeId)
            ? 'sandbox_webhook_secret' : 'webhook_secret'), $scope, $storeId)))
            ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * GetAllowedPaymentMethods
     *
     * @param string $scope
     * @param int $storeId
     * @return array|string[]
     */
    public function getAllowedPaymentMethods($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($apm = $this->getConfigValue('allowed_payment_methods', $scope, $storeId)) && is_string($apm))
            ? explode(',', $apm) : [];
    }

    /**
     * Get AllowedTermsPaymentMethods
     *
     * @param string $scope
     * @param int $storeId
     * @return array|string[]
     */
    public function getAllowedTermsPaymentMethods($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($allowedTerms = $this->getConfigValue('net_terms_allowed_payment_methods', $scope, $storeId))
            && is_string($allowedTerms)) ? explode(',', $allowedTerms) : [];
    }

    /**
     * GetBalanceSdkUrl
     *
     * @param string $scope
     * @param int $storeId
     * @return string
     */
    public function getBalanceSdkUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->resolveFromEnvironment(
            self::BALANCEPAY_SDK_LIVE_URL,
            self::BALANCEPAY_SDK_SANDBOX_URL,
            $this->getConfigValue(self::CONFIG_KEY_DEV_SDK_URL)
        );
    }

    /**
     * GetBalanceApiUrl
     *
     * @param string $path
     * @param string $scope
     * @param int $storeId
     * @return string
     */
    public function getBalanceApiUrl($path = "", $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->resolveFromEnvironment(
            self::BALANCEPAY_API_LIVE_URL,
            self::BALANCEPAY_API_SANDBOX_URL,
            $this->getConfigValue(self::CONFIG_KEY_DEV_API_URL)
        );
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return string
     */
    public function getBalanceIframeUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->resolveFromEnvironment(
            self::BALANCEPAY_IFRAME_LIVE_URL,
            self::BALANCEPAY_IFRAME_SANDBOX_URL,
            $this->getConfigValue(self::CONFIG_KEY_DEV_IFRAME_URL)
        );
    }

    /**
     * Get balance webhooks base URL for development
     *
     * @return string
     */
    public function getBalanceWebhooksBaseUrl()
    {
        $webhooksUrl = $this->getConfigValue(self::CONFIG_KEY_BALANCE_WEBHOOKS_BASE_URL);
        return !empty($webhooksUrl) ? $webhooksUrl : $this->getCurrentStore()->getBaseUrl();
    }

    /**
     * GetAllowedCustomerGroups
     *
     * @param string $scope
     * @param string $storeId
     * @return array|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllowedCustomerGroups($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $customerGroups = $this->getConfigValue('allowed_customer_groups', $scope, $storeId);
        if (!empty($customerGroups)) {
            return explode(',', $customerGroups);
        }
        return [];
    }

    /**
     * GetMerchantTermsOptions
     *
     * @param string $scope
     * @param int $storeId
     * @return array|string[]
     */
    public function getMerchantTermsOptions($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($merchantTermsOptions = $this->getConfigValue('terms_option', $scope, $storeId)) &&
            is_string($merchantTermsOptions)) ? explode(',', $merchantTermsOptions) : [];
    }

    /**
     * GetCurrentStore
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * GetReservedOrderId
     *
     * @param Quote $quote
     * @return mixed|string|null
     * @throws \Exception
     */
    public function getReservedOrderId(Quote $quote)
    {
        $reservedOrderId = $quote->getReservedOrderId();
        if (!$reservedOrderId) {
            $quote->reserveOrderId()->save();
            $reservedOrderId = $quote->getReservedOrderId();
        }
        return $reservedOrderId;
    }

    /**
     * Log
     *
     * @param string $message
     * @param string $type
     * @param array $data
     * @param string $prefix
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function log($message, $type = "debug", $data = [], $prefix = '[Balancepay] ')
    {
        if ($type !== 'debug' || $this->isDebugEnabled()) {
            if (!isset($data['store_id'])) {
                $data['store_id'] = $this->getCurrentStoreId();
            }
            switch ($type) {
                case 'error':
                    $this->logger->error($prefix . json_encode($message), $data);
                    break;
                case 'info':
                    $this->logger->info($prefix . json_encode($message), $data);
                    break;
                case 'debug':
                default:
                    $this->logger->debug($prefix . json_encode($message), $data);
                    break;
            }
        }
        return $this;
    }

    /**
     * Return bool value depends of that if payment method debug mode
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfigValue('debug');
    }
}
