<?php
namespace Balancepay\Balancepay\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Webkul\Marketplace\Helper\Data as MpDataHelper;
use Balancepay\Balancepay\Helper\Data as BalanceHelper;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Webkul Marketplace Seller Usernameverify controller.
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
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/test.log');

            $logger = new \Zend_Log();

            $logger->addWriter($writer);

            $logger->info('checking - ');
            $collection = $this->_sellerCollectionFactory->create();
            $collection->addFieldToFilter('shop_url', $profileUrl);

            $logger->info('collection size - ');
            $logger->info($collection->getSize());
            $logger->info('collection query - ');
            $logger->info($collection->getSelect()->assemble());
            $logger->info('isValidDomain - ');
            $logger->info($collection->getSelect()->assemble());
            if ($collection->getSize() || !$this->balanceHelper->isValidDomain($profileUrl)) {
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(false));
            } else {
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode(true));
            }
        }
    }
}
