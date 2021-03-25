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
    const MODULE_NAME = 'Balancepay_Balancepay';

    const BALANCEPAY_SDK_SANDBOX_URL = 'https://checkout.sandbox.getbalance.com/blnceSDK.js'; //Sandbox
    const BALANCEPAY_SDK_LIVE_URL = 'https://checkout.getbalance.com/blnceSDK.js'; //Production
    const BALANCEPAY_API_SANDBOX_URL = 'https://sandbox.app.blnce.io/api/v1/'; //Sandbox
    const BALANCEPAY_API_LIVE_URL = 'https://app.blnce.io/api/v1/'; //Production
    const BALANCEPAY_IFRAME_SANDBOX_URL = 'https://checkout.sandbox.getbalance.com/checkout.html'; //Sandbox
    const BALANCEPAY_IFRAME_LIVE_URL = 'https://checkout.getbalance.com/checkout.html'; //Production

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
     * @method __construct
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  ResourceConfig        $resourceConfig
     * @param  StoreManagerInterface $storeManager
     * @param  EncryptorInterface    $encryptor
     * @param  LoggerInterface       $logger
     * @param  UrlInterface          $urlBuilder
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
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
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Return URL Builder
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * @method resetStoreCredentials
     * @param  string                $scope Scope
     * @param  int|null              $storeId
     */
    public function updateWebhookSecret($webhookSecret = "", $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->resourceConfig->saveConfig(
            $this->getConfigPath() . ($this->isSandboxMode($scope, $storeId) ? 'sandbox_webhook_secret' : 'webhook_secret'),
            $this->encryptor->encrypt($webhookSecret),
            $scope,
            $storeId
        );
        return $this;
    }

    /**
     * @method resetStoreCredentials
     * @param  string                $scope Scope
     * @param  int|null              $storeId
     */
    public function resetStoreCredentials($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->resourceConfig->deleteConfig($this->getConfigPath() . 'active', $scope, $storeId);
        $this->resourceConfig->deleteConfig($this->getConfigPath() . ($this->isSandboxMode($scope, $storeId) ? 'sandbox_api_key' : 'api_key'), $scope, $storeId);
        return $this;
    }

    /**
     * Return config field value.
     *
     * @param string $fieldKey Field key.
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return mixed
     */
    private function getConfigValue($fieldKey, $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($this->getConfigPath() . $fieldKey);
        }
        return $this->scopeConfig->getValue(
            $this->getConfigPath() . $fieldKey,
            $scope ?: ScopeInterface::SCOPE_STORE,
            is_null($storeId) ? $this->getCurrentStoreId() : $storeId
        );
    }

    /**
     * Return bool value depends of that if payment method is active or not.
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return bool
     */
    public function isActive($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (bool)$this->getConfigValue('active', $scope, $storeId);
    }

    /**
     * Return title.
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string
     */
    public function getTitle($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->getConfigValue('title', $scope, $storeId);
    }

    /**
     * @method getLogoImageUrl
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string|null
     */
    public function getLogoImageUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        if (!($logoImage = $this->getConfigValue('logo_image', $scope, $storeId))) {
            return null;
        }
        return $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'balancepay/' . $logoImage;
    }

    /**
     * Return API key.
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string
     */
    public function getApiKey($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode($scope, $storeId) ? 'sandbox_api_key' : 'api_key'), $scope, $storeId))) ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * Return Webhook Secret.
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string
     */
    public function getWebhookSecret($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode($scope, $storeId) ? 'sandbox_webhook_secret' : 'webhook_secret'), $scope, $storeId))) ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * Return bool value depends of that if payment method sandbox mode
     * is enabled or not.
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return bool
     */
    public function isSandboxMode($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->getConfigValue('mode', $scope, $storeId) === BalancepayMethod::MODE_LIVE) ? false : true;
    }

    /**
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return array
     */
    public function getAllowedPaymentMethods($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return (($apm = $this->getConfigValue('allowed_payment_methods', $scope, $storeId)) && is_string($apm)) ? explode(',', $apm) : [];
    }

    /**
     * Return bool value depends of that if payment method debug mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfigValue('debug');
    }

    /**
     * @method getBalanceSdkUrl
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string
     */
    public function getBalanceSdkUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ? self::BALANCEPAY_SDK_SANDBOX_URL : self::BALANCEPAY_SDK_LIVE_URL);
    }

    /**
     * @method getBalanceApiUrl
     *
     * @param string $path
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string
     */
    public function getBalanceApiUrl($path = "", $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ? self::BALANCEPAY_API_SANDBOX_URL : self::BALANCEPAY_API_LIVE_URL) . (($path) ? '/' . $path : '');
    }

    /**
     * @method getBalanceIframeUrl
     *
     * @param string $scope Scope.
     * @param int    $storeId Store ID.
     *
     * @return string
     */
    public function getBalanceIframeUrl($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return ($this->isSandboxMode($scope, $storeId) ? self::BALANCEPAY_IFRAME_SANDBOX_URL : self::BALANCEPAY_IFRAME_LIVE_URL);
    }

    /**
     * @method getCurrentStore
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @method getCurrentStoreId
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @method isSingleStoreMode
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

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
     * @method log
     * @param  mixed   $message
     * @param  string  $type
     * @param  array   $data
     * @param  string  $prefix
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
