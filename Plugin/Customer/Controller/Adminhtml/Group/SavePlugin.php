<?php
namespace Balancepay\Balancepay\Plugin\Customer\Controller\Adminhtml\Group;

use Magento\Customer\Controller\Adminhtml\Group\Save;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context;
use Balancepay\Balancepay\Model\Config;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;

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
     * SavePlugin constructor.
     *
     * @param Context $httpContext
     * @param Config $config
     * @param TypeListInterface $cacheTypeList
     * @param ReinitableConfigInterface $appConfig
     */
    public function __construct(
        Context $httpContext,
        Config $config,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $appConfig
    ) {
        $this->httpContext = $httpContext;
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
        $customerGroups = [];
        $id = $subject->getRequest()->getParam('id');
        $enableBalancePayments = $subject->getRequest()->getParam('enable_balance_payments') ?? 0;
        $allowedCustomerGroups = $this->config->getAllowedCustomerGroups();
        foreach ($allowedCustomerGroups as $customerGroup) {
            if ($customerGroup != '') {
                $customerGroups[] = $customerGroup;
            }
        }
        $this->updateGroup($id, $customerGroups, $enableBalancePayments);
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