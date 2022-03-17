<?php
namespace Balancepay\Balancepay\Block\Adminhtml\Group\Edit;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Tax\Model\TaxClass\Source\Customer;
use Magento\Tax\Helper\Data;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Balancepay\Balancepay\Model\Config;
use Magento\Customer\Block\Adminhtml\Group\Edit\Form as CustomerForm;
use Magento\Customer\Model\GroupManagement;

/**
 * Adminhtml customer groups edit form
 */
class Form extends CustomerForm
{
    /**
     * @var Customer
     */
    protected $_taxCustomer;

    /**
     * @var Data
     */
    protected $_taxHelper;

    /**
     * @var GroupRepositoryInterface
     */
    protected $_groupRepository;

    /**
     * @var GroupInterfaceFactory
     */
    protected $groupDataFactory;

    /**
     * @var SystemStore
     */
    private $systemStore;

    /**
     * @var GroupExcludedWebsiteRepositoryInterface
     */
    private $groupExcludedWebsiteRepository;

    /**\
     * @var Config
     */
    private $config;

    /**
     * Form constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Customer $taxCustomer
     * @param Data $taxHelper
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupDataFactory
     * @param Config $config
     * @param SystemStore|null $systemStore
     * @param GroupExcludedWebsiteRepositoryInterface|null $groupExcludedWebsiteRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Customer $taxCustomer,
        Data $taxHelper,
        GroupRepositoryInterface $groupRepository,
        GroupInterfaceFactory $groupDataFactory,
        Config $config,
        SystemStore $systemStore = null,
        GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsiteRepository = null,
        array $data = []
    ) {
        $this->_taxCustomer = $taxCustomer;
        $this->_taxHelper = $taxHelper;
        $this->_groupRepository = $groupRepository;
        $this->groupDataFactory = $groupDataFactory;
        $this->config = $config;
        $this->systemStore = $systemStore ?: ObjectManager::getInstance()->get(SystemStore::class);
        $this->groupExcludedWebsiteRepository = $groupExcludedWebsiteRepository
            ?: ObjectManager::getInstance()->get(GroupExcludedWebsiteRepositoryInterface::class);
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $taxCustomer,
            $taxHelper,
            $groupRepository,
            $groupDataFactory,
            $data,
            $systemStore,
            $groupExcludedWebsiteRepository
        );
    }

    /**
     * Prepare form for render
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $form = $this->_formFactory->create();
        $groupId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        $customerGroupExcludedWebsites = [];
        if ($groupId === null) {
            $customerGroup = $this->groupDataFactory->create();
            $defaultCustomerTaxClass = $this->_taxHelper->getDefaultCustomerTaxClass();
        } else {
            $customerGroup = $this->_groupRepository->getById($groupId);
            $defaultCustomerTaxClass = $customerGroup->getTaxClassId();
            $customerGroupExcludedWebsites = $this->groupExcludedWebsiteRepository->getCustomerGroupExcludedWebsites(
                $groupId
            );
        }
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Group Information')]);
        $validateClass = sprintf(
            'required-entry validate-length maximum-length-%d',
            GroupManagement::GROUP_CODE_MAX_LENGTH
        );
        $name = $fieldset->addField(
            'customer_group_code',
            'text',
            [
                'name' => 'code',
                'label' => __('Group Name'),
                'title' => __('Group Name'),
                'note' => __(
                    'Maximum length must be less then %1 characters.',
                    GroupManagement::GROUP_CODE_MAX_LENGTH
                ),
                'class' => $validateClass,
                'required' => true
            ]
        );

        if ($customerGroup->getId() == 0 && $customerGroup->getCode()) {
            $name->setDisabled(true);
        }

        $fieldset->addField(
            'tax_class_id',
            'select',
            [
                'name' => 'tax_class',
                'label' => __('Tax Class'),
                'title' => __('Tax Class'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $this->_taxCustomer->toOptionArray(),
            ]
        );
        $fieldset->addField(
            'enable_balance_payments',
            'checkbox',
            [
                'name' => 'enable_balance_payments',
                'label' => __('Enable Balance payments'),
                'title' => __('Enable Balance payments'),
                'class' => '',
                'value' => 1,
                'checked' => $this->checkCustomerGroup($groupId),
            ]
        );

        $fieldset->addField(
            'customer_group_excluded_website_ids',
            'multiselect',
            [
                'name' => 'customer_group_excluded_websites',
                'label' => __('Excluded Website(s)'),
                'title' => __('Excluded Website(s)'),
                'required' => false,
                'can_be_empty' => true,
                'values' => $this->systemStore->getWebsiteValuesForForm(),
                'note' => __('Select websites you want to exclude from this customer group.')
            ]
        );

        if ($customerGroup->getId() !== null) {
            // If edit add id
            $form->addField('id', 'hidden', ['name' => 'id', 'value' => $customerGroup->getId()]);
        }

        if ($this->_backendSession->getCustomerGroupData()) {
            $form->addValues($this->_backendSession->getCustomerGroupData());
            $this->_backendSession->setCustomerGroupData(null);
        } else {
            // TODO: need to figure out how the DATA can work with forms
            $form->addValues(
                [
                    'id' => $customerGroup->getId(),
                    'customer_group_code' => $customerGroup->getCode(),
                    'tax_class_id' => $defaultCustomerTaxClass,
                    'customer_group_excluded_website_ids' => $customerGroupExcludedWebsites
                ]
            );
        }

        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setAction($this->getUrl('customer/*/save'));
        $form->setMethod('post');
        $this->setForm($form);
    }

    /**
     * CheckCustomerGroup
     *
     * @param int $groupId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function checkCustomerGroup($groupId)
    {
        $arrCustomerGroup = [];
        $allowedCustomerGroups = $this->config->getAllowedCustomerGroups();
        if (!empty($allowedCustomerGroups)) {
            foreach ($allowedCustomerGroups as $customerGroup) {
                if ($customerGroup != '') {
                    $arrCustomerGroup[] = $customerGroup;
                }
            }
        }
        $isCustomerGroupAllowed = in_array($groupId, $arrCustomerGroup);
        if ($isCustomerGroupAllowed) {
            return true;
        }
        return false;
    }
}
