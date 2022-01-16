<?php
namespace Balancepay\Balancepay\Controller\Adminhtml\Seller;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Webkul\Marketplace\Helper\Data as MpDataHelper;
use Magento\Framework\Json\Helper\Data;
use Balancepay\Balancepay\Helper\Data as BalanceHelper;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Marketplace Seller Shop URL Verify controller.
 */
class Usernameverify extends Action
{
    /**
     * @var Data
     */
    protected $_jsonHelper;

    /**
     * @var BalanceHelper
     */
    protected $balanceHelper;

    /**
     * @var CollectionFactory
     */
    protected $_sellerCollectionFactory;

    /**
     * Usernameverify constructor.
     * @param Context $context
     * @param Data $jsonHelper
     * @param BalanceHelper $balanceHelper
     * @param CollectionFactory $sellerCollectionFactory
     */
    public function __construct(
        Context $context,
        Data $jsonHelper,
        BalanceHelper $balanceHelper,
        CollectionFactory $sellerCollectionFactory
    ) {
        $this->_jsonHelper = $jsonHelper;
        $this->balanceHelper = $balanceHelper;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Verify seller shop URL exists or not
     *
     * @return \Magento\Framework\App\Response\Http
     */
    public function execute()
    {
        $profileUrl = trim($this->getRequest()->getParam("profileurl", ""));
        if ($profileUrl == "" || $profileUrl == MpDataHelper::MARKETPLACE_ADMIN_URL) {
            $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(true));
        } else {
            $collection = $this->_sellerCollectionFactory->create();
            $collection->addFieldToFilter('shop_url', $profileUrl);
            if (!$collection->getSize() && $this->balanceHelper->isValidDomain($profileUrl)) {
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(false));
            } else {
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(true));
            }
        }
    }
}
