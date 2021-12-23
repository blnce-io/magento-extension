<?php
namespace Balancepay\Balancepay\Ui\Component\Create\Form;

use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;

class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $customerTree;

    /**
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param RequestInterface $request
     * @param SellerCollection $sellerCollectionFactory
     * @param RequestFactory $requestFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory,
        RequestInterface $request,
        SellerCollection $sellerCollectionFactory,
        RequestFactory $requestFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->request = $request;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        $this->requestFactory = $requestFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getCustomerTree();
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     */
    protected function getCustomerTree()
    {
       $loadVendor =  $this->loadByVendor();
        return $loadVendor;
    }
    /**
     * @param null $store
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByVendor($store = null)
    {
        $options = [];
        try {
            $response = $this->requestFactory
                ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                ->setTopic('vendors')
                ->process();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $model = $this->getAllSellerCollectionObj();
        $vendorid = [];
        foreach ($model as $value) {
            $vendorid[] = $value->getVendorid();
        }
        foreach ($response as $label => $value) {
            if (!in_array($value['id'], $vendorid)) {
                $options[] = array('label' => $value['businessName'], 'value' => $value['id']);
            }
        }
        return $options;
    }

    /**
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getAllSellerCollectionObj()
    {
        $collection = $this->getSellerCollection();
        $collection->addFieldToFilter('store_id', $this->getCurrentStoreId());
        if (!$collection->getSize()) {
            $collection = $this->getSellerCollection();
            $collection->addFieldToFilter('store_id', 0);
        }

        return $collection;
    }

    /**
     * Get Seller Collection
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerCollection()
    {
        return $this->_sellerCollectionFactory->create();
    }

    /**
     * @return mixed
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore()->getStoreId();
    }
}
