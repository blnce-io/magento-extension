<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Backend\Model\Url;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Event\Observer;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Webkul\Marketplace\Model\SellerFactory as MpSellerFactory;
use Zend_Log_Exception;

/**
 * Webkul Marketplace CustomerRegisterSuccessObserver Observer.
 */
class BalanceCustomerRegisterSuccessObserver implements ObserverInterface
{

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var DateTime
     */
    private $_date;

    /**
     * @var MpSellerFactory
     */
    private $mpSellerFactory;

    /**
     * @var UrlRewriteFactory
     */
    private $urlRewriteFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var MpEmailHelper
     */
    private $mpEmailHelper;

    /**
     * @var Url
     */
    private $urlBackendModel;

    /**
     * BalanceCustomerRegisterSuccessObserver constructor.
     *
     * @param ManagerInterface $messageManager
     * @param MpHelper $mpHelper
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        ManagerInterface $messageManager,
        MpHelper $mpHelper,
        Config $balancepayConfig,
        RequestFactory $requestFactory,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        DateTime $date,
        MpSellerFactory $mpSellerFactory,
        UrlRewriteFactory $urlRewriteFactory,
        Session $customerSession,
        UrlInterface $urlInterface,
        MpEmailHelper $mpEmailHelper,
        Url $urlBackendModel
    ) {
        $this->_messageManager = $messageManager;
        $this->mpHelper = $mpHelper;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->_storeManager = $storeManager;
        $this->_date = $date;
        $this->mpSellerFactory = $mpSellerFactory;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->customerSession = $customerSession;
        $this->urlInterface = $urlInterface;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->urlBackendModel = $urlBackendModel;
    }

    /**
     * Observer to create Balance vendor after registration
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if (!empty($paramData['is_seller']) && !empty($paramData['profileurl']) && $paramData['is_seller'] == 1) {
                $customer = $observer->getCustomer();

                $profileurlcount = $this->mpSellerFactory->create()->getCollection();
                $profileurlcount->addFieldToFilter(
                    'shop_url',
                    $paramData['profileurl']
                );
                $vendorId = $this->createBalancePayVendor($customer->getId());
                if (!$profileurlcount->getSize()) {
                    $partnerApprovalStatus = $this->mpHelper->getIsPartnerApproval();
                    $status = $partnerApprovalStatus ? 0 : 1;
                    $customerid = $customer->getId();
                    $model = $this->mpSellerFactory->create();
                    $model->setData('is_seller', $status);
                    $model->setData('shop_url', $paramData['profileurl']);
                    $model->setData('seller_id', $customerid);
                    $model->setData('store_id', 0);
                    $model->setData('balance_vendor_id', $vendorId);
                    $model->setCreatedAt($this->_date->gmtDate());
                    $model->setUpdatedAt($this->_date->gmtDate());
                    $model->setAdminNotification(1);
                    $model->save();
                    $loginUrl = $this->urlInterface->getUrl("marketplace/account/dashboard");
                    $this->customerSession->setBeforeAuthUrl($loginUrl);
                    $this->customerSession->setAfterAuthUrl($loginUrl);

                    $helper = $this->mpHelper;
                    if ($helper->getAutomaticUrlRewrite()) {
                        $this->createSellerPublicUrls($paramData['profileurl']);
                    }
                    if ($partnerApprovalStatus) {
                        $adminStoremail = $helper->getAdminEmailId();
                        $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
                        $adminUsername = $helper->getAdminName();
                        $senderInfo = [
                            'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                            'email' => $customer->getEmail(),
                        ];
                        $receiverInfo = [
                            'name' => $adminUsername,
                            'email' => $adminEmail,
                        ];
                        $emailTemplateVariables['myvar1'] = $customer->getFirstName() . ' ' .
                            $customer->getLastName();
                        $emailTemplateVariables['myvar2'] = $this->urlBackendModel->getUrl(
                            'customer/index/edit',
                            ['id' => $customer->getId()]
                        );
                        $emailTemplateVariables['myvar3'] = $helper->getAdminName();

                        $this->mpEmailHelper->sendNewSellerRequest(
                            $emailTemplateVariables,
                            $senderInfo,
                            $receiverInfo
                        );
                    }
                } else {
                    $this->_messageManager->addError(
                        __('This Shop URL already Exists.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger(
                "Observer_CustomerRegisterSuccessObserver execute : " . $e->getMessage()
            );
            $this->_messageManager->addError($e->getMessage());
        }
    }

    /**
     * Create Balance vendor
     *
     * @param int $customerId
     * @throws Zend_Log_Exception
     */
    public function createBalancePayVendor($customerId)
    {
        if ($this->balancepayConfig->getIsBalanaceVendorRegistry() && $customerId) {
            try {
                $response = $this->requestFactory
                    ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                    ->setRequestMethod('vendors')
                    ->setTopic('create-vendors')
                    ->process();
                if (!empty($response['vendor']['id'])) {
                    return $response['vendor']['id'];
                }
                return '';
            } catch (LocalizedException $e) {
                $this->_messageManager->addExceptionMessage($e);
            }
        }
    }

    private function createSellerPublicUrls($profileurl = '')
    {
        if ($profileurl) {
            $getCurrentStoreId = $this->mpHelper->getCurrentStoreId();

            /*
            * Set Seller Profile Url
            */
            $sourceProfileUrl = 'marketplace/seller/profile/shop/' . $profileurl;
            $requestProfileUrl = $profileurl;
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $profileRequestUrl = '';
            $urlCollectionData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceProfileUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $profileRequestUrl = $value->getRequestPath();
            }
            if ($profileRequestUrl != $requestProfileUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setIdPath($idPath)
                    ->setTargetPath($sourceProfileUrl)
                    ->setRequestPath($requestProfileUrl)
                    ->save();
            }

            /*
            * Set Seller Collection Url
            */
            $sourceCollectionUrl = 'marketplace/seller/collection/shop/' . $profileurl;
            $requestCollectionUrl = $profileurl . '/collection';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $collectionRequestUrl = '';
            $urlCollectionData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceCollectionUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $collectionRequestUrl = $value->getRequestPath();
            }
            if ($collectionRequestUrl != $requestCollectionUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setIdPath($idPath)
                    ->setTargetPath($sourceCollectionUrl)
                    ->setRequestPath($requestCollectionUrl)
                    ->save();
            }

            /*
            * Set Seller Feedback Url
            */
            $sourceFeedbackUrl = 'marketplace/seller/feedback/shop/' . $profileurl;
            $requestFeedbackUrl = $profileurl . '/feedback';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $feedbackRequestUrl = '';
            $urlFeedbackData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceFeedbackUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlFeedbackData as $value) {
                $urlId = $value->getId();
                $feedbackRequestUrl = $value->getRequestPath();
            }
            if ($feedbackRequestUrl != $requestFeedbackUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setIdPath($idPath)
                    ->setTargetPath($sourceFeedbackUrl)
                    ->setRequestPath($requestFeedbackUrl)
                    ->save();
            }

            /*
            * Set Seller Location Url
            */
            $sourceLocationUrl = 'marketplace/seller/location/shop/' . $profileurl;
            $requestLocationUrl = $profileurl . '/location';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $locationRequestUrl = '';
            $urlLocationData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceLocationUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlLocationData as $value) {
                $urlId = $value->getId();
                $locationRequestUrl = $value->getRequestPath();
            }
            if ($locationRequestUrl != $requestLocationUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setIdPath($idPath)
                    ->setTargetPath($sourceLocationUrl)
                    ->setRequestPath($requestLocationUrl)
                    ->save();
            }

            /**
             * Set Seller Policy Url
             */
            $sourcePolicyUrl = 'marketplace/seller/policy/shop/' . $profileurl;
            $requestPolicyUrl = $profileurl . '/policy';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $policyRequestUrl = '';
            $urlPolicyData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourcePolicyUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlPolicyData as $value) {
                $urlId = $value->getId();
                $policyRequestUrl = $value->getRequestPath();
            }
            if ($policyRequestUrl != $requestPolicyUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setIdPath($idPath)
                    ->setTargetPath($sourcePolicyUrl)
                    ->setRequestPath($requestPolicyUrl)
                    ->save();
            }
        }
    }
}
