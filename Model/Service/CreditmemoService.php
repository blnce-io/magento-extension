<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Balancepay\Balancepay\Model\Service;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\CreditmemoCommentRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoNotifier;
use Magento\Sales\Model\Order\RefundAdapterInterface;
use Magento\Sales\Model\Service\CreditmemoService as SalesCreditmemoService;

/**
 * Class CreditmemoService
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditmemoService extends SalesCreditmemoService
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var CreditmemoCommentRepositoryInterface
     */
    protected $commentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var CreditmemoNotifier
     */
    protected $creditmemoNotifier;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var RefundAdapterInterface
     */
    private $refundAdapter;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoCommentRepositoryInterface $creditmemoCommentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param CreditmemoNotifier $creditmemoNotifier
     * @param PriceCurrencyInterface $priceCurrency
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoCommentRepositoryInterface $creditmemoCommentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        CreditmemoNotifier $creditmemoNotifier,
        PriceCurrencyInterface $priceCurrency,
        ManagerInterface $eventManager
    ) {
        parent::construct(
            $creditmemoRepository,
            $creditmemoCommentRepository,
            $searchCriteriaBuilder,
            $filterBuilder,
            $creditmemoNotifier,
            $priceCurrency,
            $eventManager
        );
    }

    /**
     * Prepare creditmemo to refund and save it.
     *
     * @param CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return CreditmemoInterface
     * @throws LocalizedException
     */
    public function refund(
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        $this->validateForRefund($creditmemo);
        $creditmemo->setState(Creditmemo::STATE_OPEN);

        $connection = $this->getResource()->getConnection('sales');
        $connection->beginTransaction();
        try {
            $invoice = $creditmemo->getInvoice();
            if ($invoice && !$offlineRequested) {
                $invoice->setIsUsedForRefund(true);
                $invoice->setBaseTotalRefunded(
                    $invoice->getBaseTotalRefunded() + $creditmemo->getBaseGrandTotal()
                );
                $creditmemo->setInvoiceId($invoice->getId());
                $this->getInvoiceRepository()->save($creditmemo->getInvoice());
            }
            $order = $this->getRefundAdapter()->refund(
                $creditmemo,
                $creditmemo->getOrder(),
                !$offlineRequested
            );
            $this->creditmemoRepository->save($creditmemo);
            $this->getOrderRepository()->save($order);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new LocalizedException(__($e->getMessage()));
        }

        return $creditmemo;
    }

    /**
     * Initializes RefundAdapterInterface dependency.
     *
     * @return RefundAdapterInterface
     * @deprecated 100.1.3
     */
    private function getRefundAdapter()
    {
        if ($this->refundAdapter === null) {
            $this->refundAdapter = ObjectManager::getInstance()
                ->get(RefundAdapterInterface::class);
        }
        return $this->refundAdapter;
    }

    /**
     * Initializes ResourceConnection dependency.
     *
     * @return ResourceConnection|mixed
     * @deprecated 100.1.3
     */
    private function getResource()
    {
        if ($this->resource === null) {
            $this->resource = ObjectManager::getInstance()
                ->get(ResourceConnection::class);
        }
        return $this->resource;
    }

    /**
     * Initializes OrderRepositoryInterface dependency.
     *
     * @return OrderRepositoryInterface
     * @deprecated 100.1.3
     */
    private function getOrderRepository()
    {
        if ($this->orderRepository === null) {
            $this->orderRepository = ObjectManager::getInstance()
                ->get(OrderRepositoryInterface::class);
        }
        return $this->orderRepository;
    }

    /**
     * Initializes InvoiceRepositoryInterface dependency.
     *
     * @return InvoiceRepositoryInterface
     * @deprecated 100.1.3
     */
    private function getInvoiceRepository()
    {
        if ($this->invoiceRepository === null) {
            $this->invoiceRepository = ObjectManager::getInstance()
                ->get(InvoiceRepositoryInterface::class);
        }
        return $this->invoiceRepository;
    }
}
