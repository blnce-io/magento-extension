<?php
namespace Balancepay\Balancepay\Plugin\Customer\Controller\Adminhtml\Group;

use Magento\Customer\Controller\Adminhtml\Group\Save;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context;
use Balancepay\Balancepay\Model\Config;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\App\Cache\Frontend\Pool;

class SavePlugin
{
    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var ReinitableConfigInterface
     */
    protected $appConfig;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @param Context $httpContext
     * @param Config $config
     * @param TypeListInterface $cacheTypeList
     * @param ReinitableConfigInterface $appConfig
     * @param CollectionFactory $collectionFactory
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        Context $httpContext,
        Config $config,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $appConfig,
        CollectionFactory $collectionFactory,
        Pool $cacheFrontendPool
    ) {
        $this->httpContext = $httpContext;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $appConfig;
        $this->collectionFactory = $collectionFactory;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * AfterExecute
     *
     * @param Save $subject
     * @param mixed $result
     */
    public function afterExecute(
        Save $subject,
        $result
    ) {
        $customerGroups = [];
        $customerGroupData =  $this->collectionFactory->create()->setOrder('customer_group_id', 'DESC')->getFirstItem();
        $id = $subject->getRequest()->getParam('id') ?? $customerGroupData->getCustomerGroupId();
        $enableBalancePayments = $subject->getRequest()->getParam('enable_balance_payments') ?? 0;
        $allowedCustomerGroups = $this->config->getAllowedCustomerGroups();
        foreach ($allowedCustomerGroups as $customerGroup) {
            if ($customerGroup != '') {
                $customerGroups[] = $customerGroup;
            }
        }
        $this->updateGroup($id, $customerGroups, $enableBalancePayments);
        $this->flushCache();
        $this->appConfig->reinit();
        return $result;
    }

    /**
     * Flush Cache
     *
     * @return void
     */
    public function flushCache()
    {
        $_types = [
            'config',
            'layout',
            'block_html',
            'collections',
            'reflection',
            'db_ddl',
            'eav',
            'config_integration',
            'config_integration_api',
            'full_page',
            'translate',
            'config_webservice'
        ];

        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Update Group
     *
     * @param int $id
     * @param array $customerGroups
     * @param bool $enableBalancePayments
     * @return void
     */
    public function updateGroup($id, $customerGroups, $enableBalancePayments)
    {
        $isCustomerGroupAllowed = in_array($id, $customerGroups);
        if ($enableBalancePayments && (!$isCustomerGroupAllowed)) {
            $customerGroups[] = $id;
        } elseif ($isCustomerGroupAllowed && ($key = array_search($id, $customerGroups)) !== false) {
            unset($customerGroups[$key]);
        }
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->config->updateCustomerGroup($scope, implode(",", array_unique($customerGroups)));
    }
}
