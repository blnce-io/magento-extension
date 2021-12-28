<?php
namespace Balancepay\Balancepay\Ui\Component\Create\Form;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;

class Options implements OptionSourceInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var SellerCollection
     */
    protected $sellerCollectionFactory;

    /**
     * @param SellerCollection $sellerCollectionFactory
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        SellerCollection $sellerCollectionFactory,
        RequestFactory $requestFactory
    ) {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $response = $this->requestFactory
                ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                ->setTopic('vendors')
                ->process();

            $model = $this->getAllSellerCollectionObj();
            $vendorid = array_map(function($item){ return $item['balance_vendor_id']; }, $model->toArray(['balance_vendor_id'])['items']);
            foreach ($response as $label => $value) {
                if (!in_array($value['id'], $vendorid)) {
                    $options[] = array('label' => $value['businessName'], 'value' => $value['id']);
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $options;
    }

    /**
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getAllSellerCollectionObj()
    {
        $collection = $this->sellerCollectionFactory->create()->addFieldToSelect('balance_vendor_id')
            ->addFieldToFilter('balance_vendor_id', ['neq' => '']);
        return $collection;
    }
}
