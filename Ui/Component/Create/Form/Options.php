<?php
namespace Balancepay\Balancepay\Ui\Component\Create\Form;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
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
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @param SellerCollection $sellerCollectionFactory
     * @param RequestFactory $requestFactory
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        SellerCollection $sellerCollectionFactory,
        RequestFactory $requestFactory,
        MessageManagerInterface $messageManager
    ) {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->requestFactory = $requestFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $response = $this->requestFactory
                ->create(RequestFactory::VENDORS_REQUEST_METHOD)
                ->setRequestMethod('vendors')
                ->setTopic('vendors')
                ->process();

            $model = $this->getAllSellerCollectionObj();
            $vendorid = array_map(function ($item) {
                return $item['balance_vendor_id'];
            }, $model->toArray(['balance_vendor_id'])['items']);
            foreach ($response as $label => $value) {
                if (!in_array($value['id'], $vendorid)) {
                    $options[] = ['label' => $value['businessName'], 'value' => $value['id']];
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $options;
    }

    /**
     * Get all sellers
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getAllSellerCollectionObj()
    {
        $collection = $this->sellerCollectionFactory->create()->addFieldToSelect('balance_vendor_id')
            ->addFieldToFilter('balance_vendor_id', ['neq' => '']);
        return $collection;
    }
}
