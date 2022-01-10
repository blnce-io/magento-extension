<?php
namespace Balancepay\Balancepay\Controller\Adminhtml\Seller;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Helper\Data as MpDataHelper;

/**
 * Marketplace Seller Shop URL Verify controller.
 */
class Usernameverify extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $_sellerCollectionFactory;

    /**
     * Initialize dependencies
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_jsonHelper = $jsonHelper;
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
            $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(1));
        } else {
            $collection = $this->_sellerCollectionFactory->create();
            $collection->addFieldToFilter('shop_url', $profileUrl);
            if (!$collection->getSize() && $this->isValidDomain($profileUrl)) {
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(0));
            } else {
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(1));
            }
        }
    }

    /**
     * Is valid domain
     *
     * @param mixed $domain_name
     * @return bool
     */
    public function isValidDomain($domain_name): bool
    {
        if (preg_match(
            '/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/',
            $domain_name
        )) {
            return true;
        }
        return false;
    }
}
