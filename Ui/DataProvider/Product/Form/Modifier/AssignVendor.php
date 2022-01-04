<?php
namespace Balancepay\Balancepay\Ui\DataProvider\Product\Form\Modifier;

use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;

class AssignVendor extends AbstractModifier
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param MpProductCollection $mpProductCollectionFactory
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        MpProductCollection $mpProductCollectionFactory,
        RequestFactory $requestFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Modify Data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Modify Meta
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                'assign_vendor' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Assign Product to Vendor'),
                                'componentType' => Fieldset::NAME,
                                'dataScope' => 'data.product.assign_vendor',
                                'collapsible' => false,
                                'sortOrder' => 5,
                            ],
                        ],
                    ],
                    'children' => [
                        'assignseller_field' => $this->getSellerField()
                    ],
                ]
            ]
        );
        return $meta;
    }

    /**
     * GetSellerField
     *
     * @return mixed
     */
    public function getSellerField()
    {
        $sellerId = $this->getProductSeller();
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Select Vendor'),
                        'componentType' => Field::NAME,
                        'formElement' => Select::NAME,
                        'dataScope' => 'vendor_id',
                        'dataType' => Text::NAME,
                        'sortOrder' => 10,
                        'options' => $this->getAllSellerCollectionObj(),
                        'value' => $sellerId
                    ],
                ],
            ],
        ];
    }

    /**
     * GetProductSeller is used to get the vendor id by the product id
     *
     * @return int||null
     */
    public function getProductSeller()
    {
        $product = $this->coreRegistry->registry('product');
        $productId = $product->getId();
        $sellerId = $this->getSellerIdByProductId($productId);
        return $sellerId;
    }

    /**
     * Return the seller Id by product id.
     *
     * @param string $productId
     * @return mixed
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $sellerId = $collection->getFirstItem()->getVendorId();
        return $sellerId;
    }

    /**
     * Get all seller collection
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllSellerCollectionObj()
    {
        $options = [];
        $response = $this->requestFactory
            ->create(RequestFactory::VENDORS_REQUEST_METHOD)
            ->setRequestMethod('vendors')
            ->setTopic('vendors')
            ->process();

        $options[] = ['label' => 'Select Balance Vendor', 'value' => ''];
        foreach ($response as $label => $value) {
            $options[] = ['label' => $value['businessName'], 'value' => $value['id']];
        }
        return $options;
    }
}
