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

namespace Balancepay\Balancepay\Observer\Config;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Store\Model\ScopeInterface;

class Save implements ObserverInterface
{
    /**
     * @var BalancepayConfig
     */
    private $balancepayConfig;

    /**
     * @var ReinitableConfigInterface
     */
    private $appConfig;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var AppEmulation
     */
    private $appEmulation;

    /**
     * @method __construct
     * @param  BalancepayConfig          $balancepayConfig
     * @param  ReinitableConfigInterface $appConfig
     * @param  TypeListInterface         $cacheTypeList
     * @param  MessageManagerInterface   $messageManager
     * @param  RequestFactory            $requestFactory
     * @param  AppEmulation              $appEmulation
     */
    public function __construct(
        BalancepayConfig $balancepayConfig,
        ReinitableConfigInterface $appConfig,
        TypeListInterface $cacheTypeList,
        MessageManagerInterface $messageManager,
        RequestFactory $requestFactory,
        AppEmulation $appEmulation
    ) {
        $this->balancepayConfig = $balancepayConfig;
        $this->appConfig = $appConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->messageManager = $messageManager;
        $this->requestFactory = $requestFactory;
        $this->appEmulation = $appEmulation;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        if (($storeId = $observer->getEvent()->getStore())) {
            $scope = ScopeInterface::SCOPE_STORE;
        } elseif (($websiteId = $observer->getEvent()->getWebsite())) {
            $storeId = $websiteId;
            $scope = ScopeInterface::SCOPE_WEBSITE;
        } else {
            $storeId = 0;
        }

        $this->balancepayConfig->getApiKey();

        $this->appEmulation->stopEnvironmentEmulation();
        if ($storeId && $scope !== ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
            $this->appEmulation->startEnvironmentEmulation(
                !empty($websiteId)
                    ? $this->balancepayConfig->getStoreManager()->getWebsite($websiteId)->getDefaultStore()->getId()
                    : $storeId,
                Area::AREA_FRONTEND,
                true
            );
        }

        if ($this->balancepayConfig->isActive()) {
            $errorMessage = __('Balance API key is missing/invalid! (Failed to register webhooks).');
            if ($this->balancepayConfig->getApiKey()) {
                try {
                    $this->requestFactory
                        ->create(RequestFactory::WEBHOOKS_KEYS_REQUEST_METHOD)
                        ->process()
                        ->update($scope, $storeId);

                    $this->requestFactory
                        ->create(RequestFactory::WEBHOOKS_REQUEST_METHOD)
                        ->setTopic('checkout/charged')
                        ->process();

                    $this->requestFactory
                        ->create(RequestFactory::WEBHOOKS_REQUEST_METHOD)
                        ->setTopic('transaction/confirmed')
                        ->process();

                    $this->appEmulation->stopEnvironmentEmulation();
                    return $this->messageManager->addSuccess(__('Balance API key is valid!
                    (Webhooks have been successfully registered)'));
                } catch (\Exception $e) {
                    $this->balancepayConfig->updateBalancePayStatus($scope);
                }
            } else {
                $this->appEmulation->stopEnvironmentEmulation();
                $this->balancepayConfig->resetStoreCredentials($scope, $storeId);
                throw new LocalizedException(new Phrase('Can\' enable Balance payments, API key is missing!'));
            }
        }
    }

    /**
     * CleanConfigCache
     *
     * @return $this
     */
    private function cleanConfigCache()
    {
        try {
            $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            $this->appConfig->reinit();
        } catch (\Exception $e) {
            $this->messageManager->addNoticeMessage(__('For some reason,
            Balance (payment) couldn\'t clear your config cache,
            please clear the cache manually. (Exception message: %1)', $e->getMessage()));
        }
        return $this;
    }
}
