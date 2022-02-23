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

    public const BALANCEPAY_SDK_SANDBOX_URL = 'https://checkout-v2.sandbox.getbalance.com/sdk.js'; //Sandbox
    public const BALANCEPAY_SDK_LIVE_URL = 'https://checkout-v2.getbalance.com/blnceSDK.js'; //Production
    public const BALANCEPAY_API_SANDBOX_URL = 'https://sandbox.app.blnce.io/api/v1/'; //Sandbox
    public const BALANCEPAY_API_LIVE_URL = 'https://app.blnce.io/api/v1/'; //Production
    public const BALANCEPAY_IFRAME_SANDBOX_URL = 'https://checkout-v2.sandbox.getbalance.com/checkout.html'; //Sandbox
    public const BALANCEPAY_IFRAME_LIVE_URL = 'https://checkout-v2.getbalance.com/checkout.html'; //Production
    public const BALANCEPAY_DASHBOARD_SANDBOX_URL = 'https://dashboard.sandbox.getbalance.com'; //Sandbox
    public const BALANCEPAY_DASHBOARD_LIVE_URL = 'https://dashboard.getbalance.com'; //Production

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
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  ResourceConfig        $resourceConfig
     * @param  StoreManagerInterface $storeManager
     * @param  EncryptorInterface    $encryptor
     * @param  LoggerInterface       $logger
     * @param  UrlInterface          $urlBuilder
     * @param  DateTime              $dateTime
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
     * Return config path.
     *
     * @return string
     */
    private function getConfigPath()
    {
        return sprintf('payment/%s/', BalancepayMethod::METHOD_CODE);
    }

    /**
     * Update Balance Pay status
     *
     * @param string $scope
     * @param int $storeId
     */
    public function updateCustomerGroup($scope = ScopeInterface::SCOPE_STORE, $value, $storeId = null)
    {
        $this->resourceConfig->saveConfig(
            $this->getConfigPath() . 'allowed_customer_groups',
            $value,
            $scope,
            $storeId
        );
    }

    /**
     * Return store manager
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
     * GetGmtDate
     *
     * @return false|string
     */
    public function getGmtDate()
    {
        return $this->dateTime->gmtDate();
    }

    /**
     * Reset store credentials
     *
     * @param string $webhookSecret
     * @param string $scope
     * @param string $storeId
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
     * ResetStoreCredentials
     *
     * @param string $scope
     * @param string $storeId
     * @return $this
     */
    public function resetStoreCredentials($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->resourceConfig->deleteConfig($this->getConfigPath() . 'active', $scope, $storeId);
        $this->resourceConfig->deleteConfig($this->getConfigPath() . ($this->isSandboxMode($scope, $storeId) ?
                'sandbox_api_key' : 'api_key'), $scope, $storeId);
        return $this;
    }

    /**
     * GetConfigValue
     *
     * @param string $fieldKey
     * @param string $scope
     * @param string $storeId
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
            (!$storeId) ? $this->getCurrentStoreId() : $storeId
        );
    }

    /**
     * Check isActive
     *
     * @param string $scope
     * @param string $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isActive($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue('active', $scope, $storeId);
    }

    /**
     * Get Title
     *
     * @param mixed $scope
     * @param string $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTitle($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->getConfigValue('title', $scope, $storeId);
    }

    /**
     * GetIsAuth
     *
     * @param string $scope
     * @param string $storeId
     * @return bool
     */
    public function getIsAuth($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue('is_auth', $scope, $storeId);
    }

    /**
     * GetIsBalanaceVendorRegistry
     *
     * @param string $scope
     * @param string $storeId
     * @return bool
     */
    public function getIsBalanaceVendorRegistry($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue('is_balance_vendors_seller_register', $scope, $storeId);
    }

    /**
     * GetLogoImageUrl
     *
     * @param string $scope
     * @param string $storeId
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
     * Return API key
     *
     * @param string $scope
     * @param string $storeId
     * @return string|null
     */
    public function getApiKey($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode($scope, $storeId) ?
            'sandbox_api_key' : 'api_key'), $scope, $storeId))) ?
            $this->encryptor->decrypt($val) : null;
    }

    /**
     * GetWebhookSecret
     *
     * @param string $scope
     * @param string $storeId
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWebhookSecret($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode($scope, $storeId) ?
            'sandbox_webhook_secret' : 'webhook_secret'), $scope, $storeId))) ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * Check isSandboxMode
     *
     * @param string $scope
     * @param string $storeId
     * @return bool
     */
    public function isSandboxMode($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->getConfigValue('mode', $scope, $storeId) === BalancepayMethod::MODE_LIVE) ? false : true;
    }

    /**
     * GetAllowedPaymentMethods
     *
     * @param string $scope
     * @param string $storeId
     * @return array|string[]
     */
    public function getAllowedPaymentMethods($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($apm = $this->getConfigValue('allowed_payment_methods', $scope, $storeId))
            && is_string($apm)) ? explode(',', $apm) : [];
    }

    /**
     * Check isDebugEnabled
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfigValue('debug');
    }

    /**
     * GetBalanceSdkUrl
     *
     * @param string $scope
     * @param string $storeId
     * @return string
     */
    public function getBalanceSdkUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ?
            self::BALANCEPAY_SDK_SANDBOX_URL : self::BALANCEPAY_SDK_LIVE_URL);
    }

    /**
     * GetBalanceApiUrl
     *
     * @param string $path
     * @param string $scope
     * @param string $storeId
     * @return string
     */
    public function getBalanceApiUrl($path = "", $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ?
                self::BALANCEPAY_API_SANDBOX_URL : self::BALANCEPAY_API_LIVE_URL) . (($path) ? '/' . $path : '');
    }

    /**
     * GetBalanceIframeUrl
     *
     * @param string $scope
     * @param string $storeId
     * @return string
     */
    public function getBalanceIframeUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ?
            self::BALANCEPAY_IFRAME_SANDBOX_URL : self::BALANCEPAY_IFRAME_LIVE_URL);
    }

    /**
     * @param $scope
     * @param $storeId
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
     * GetBalanceDashboardUrl
     *
     * @param string $scope
     * @param string $storeId
     * @return string
     */
    public function getBalanceDashboardUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ?
            self::BALANCEPAY_DASHBOARD_SANDBOX_URL : self::BALANCEPAY_DASHBOARD_LIVE_URL);
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
     * Check isSingleStoreMode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
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
     * Logs
     *
     * @param mixed $message
     * @param string $type
     * @param array $data
     * @param string $prefix
     * @return $this
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
}
