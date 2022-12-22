<?php

namespace Balancepay\Balancepay\Controller\Adminhtml\Order;

use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;
use Magento\Backend\App\Action;

class Cancel extends \Magento\Sales\Controller\Adminhtml\Order implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Sales::cancel';
    public const BALANCEPAY_CHECKOUT_TRANSACTION_ID = 'balancepay_checkout_transaction_id';
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * Cancel constructor.
     *
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        RequestFactory $requestFactory
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
        $this->requestFactory = $requestFactory;
    }

    /**
     * Cancel order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isValidPostRequest()) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }

        $order = $this->_initOrder();
        if ($order) {
            try {
                if ($order->getPayment()->getAdditionalInformation(self::BALANCEPAY_CHECKOUT_TRANSACTION_ID)) {
                    $response = $this->requestFactory
                        ->create(RequestFactory::TRANSACTION_CANCEL_REQUEST_METHOD)
                        ->setTransactionId($order
                            ->getPayment()
                            ->getAdditionalInformation(self::BALANCEPAY_CHECKOUT_TRANSACTION_ID))
                        ->process();
                    $status = $response['status'] ?? '';
                    if ($status == 'canceled') {
                        $this->orderManagement->cancel($order->getEntityId());
                        $this->messageManager->addSuccessMessage(__('You canceled the order.'));
                    } else {
                        $this->messageManager->addErrorMessage(__('You can not canceled the order.'));
                    }
                } else {
                    $this->orderManagement->cancel($order->getEntityId());
                    $this->messageManager->addSuccessMessage(__('You canceled the order.'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }
}
