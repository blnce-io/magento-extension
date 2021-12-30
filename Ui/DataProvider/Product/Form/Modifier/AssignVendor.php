<?php
namespace Balancepay\Balancepay\Ui\DataProvider\Product\Form\Modifier;

use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;

class AssignVendor extends AbstractModifier
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Webkul\Marketplace\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        SellerCollection $sellerCollectionFactory,
        \Webkul\Marketplace\Helper\Data $helper,
        MpProductCollection $mpProductCollectionFactory,
        RequestFactory $requestFactory
    )
    {
        $this->coreRegistry = $coreRegistry;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->helper = $helper;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->requestFactory = $requestFactory;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

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
     * getSellerField is used to show the field for assign vendor.
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
     * getProductSeller is used to get the vendor id by the product id
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
     * @return int||null
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $sellerId = $collection->getFirstItem()->getVendorId();
        return $sellerId;
    }

    /**
     * @return mixed
     */
    public function getAllSellerCollectionObj()
    {
        $options = [];
        $response = $this->requestFactory
            ->create(RequestFactory::VENDORS_REQUEST_METHOD)
            ->setRequestMethod('vendors')
            ->setTopic('vendors')
            ->process();

        $options[] = array('label' => 'Select Balance Vendor', 'value' => '');
        foreach ($response as $label => $value) {
            $options[] = array('label' => $value['businessName'], 'value' => $value['id']);
        }
        return $options;
    }
}
