<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;

/**
 * Class BuyerCustomerAttributeV2
 *  Balancepay\Balancepay\Setup\Patch\Data
 */
class BuyerCustomerAttributeV2 implements DataPatchInterface
{
    /**
     * "Account Type" customer attribute code
     */
    public const ATTRIBUTE_CODE = 'buyer_id';

    /**
     * "Account Type" customer attribute label
     */
    public const ATTRIBUTE_LABEL = 'Buyer Id';

    /**
     * Resource Setup Model
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * Eav attribute set model factory
     *
     * @var SetFactory
     */
    private $attributeSetFactory;

    /**
     * Customer attribute resource model
     *
     * @var Attribute
     */
    private $attributeResource;

    /**
     * CreateMemberAccountAttribute constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SetFactory $attributeSetFactory
     * @param Attribute $attributeResource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        SetFactory $attributeSetFactory,
        Attribute $attributeResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeResource = $attributeResource;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Apply patch
     *
     * @return $this|DataPatchInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->addAttribute(
            Customer::ENTITY,
            self::ATTRIBUTE_CODE,
            [
                'label' => self::ATTRIBUTE_LABEL,
                'input' => 'text',
                'type' => 'varchar',
                'required' => false,
                'position' => 35,
                'visible' => true,
                'system' => false,
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE);
        $attribute->addData([
            'used_in_forms' => [
                'adminhtml_customer',
            ]
        ]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $attribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ]);
        $this->attributeResource->save($attribute);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
