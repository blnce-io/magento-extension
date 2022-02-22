<?php
namespace Balancepay\Balancepay\Plugin\Customer\Controller\Adminhtml\Group;

use Magento\Customer\Controller\Adminhtml\Group\Save;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;

class SavePlugin
{
    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

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
     * SavePlugin constructor.
     *
     * @param Context $httpContext
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     */
    public function __construct(
        Context $httpContext,
        ResourceConnection $resourceConnection,
        Config $config,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $appConfig
    ) {
        $this->httpContext = $httpContext;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $appConfig;
    }

    /**
     * @param Save $subject
     * @param $result
     */
    public function afterExecute(
        Save $subject,
        $result
    )
    {
        $arrCustomerGroup = [];
        $id = $subject->getRequest()->getParam('id');
        $enableBalancePayments = $subject->getRequest()->getParam('enable_balance_payments') ?? 0;
        $updateQuery = "UPDATE `customer_group` SET `enable_balance_payments` =" . $enableBalancePayments . " WHERE `customer_group_id` =" . $id;
        $this->getConnection()->query($updateQuery);

        $allowedCustomerGroups = $this->config->getAllowedCustomerGroups();
        if (!empty($allowedCustomerGroups)) {
            foreach ($allowedCustomerGroups as $customerGroup) {
                if ($customerGroup != '') {
                    $arrCustomerGroup[] = $customerGroup;
                }
            }
            $this->updateGroup($id, $arrCustomerGroup, $enableBalancePayments);
        }
        $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);
        $this->appConfig->reinit();
        return $result;
    }

    /**
     * @param $id
     * @param $arrCustomerGroup
     * @param $enableBalancePayments
     * @return void
     */
    public function updateGroup($id, $arrCustomerGroup, $enableBalancePayments)
    {
        $isCustomerGroupAllowed = in_array($id, $arrCustomerGroup);
        if ($enableBalancePayments && (!$isCustomerGroupAllowed)) {
            $arrCustomerGroup[] = $id;
        } elseif ($isCustomerGroupAllowed) {
            if (($key = array_search($id, $arrCustomerGroup)) !== false) {
                unset($arrCustomerGroup[$key]);
            }
        }
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->config->updateCustomerGroup($scope, implode(",", array_unique($arrCustomerGroup)));
    }

    /**
     * @return AdapterInterface
     */
    protected function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }
}
