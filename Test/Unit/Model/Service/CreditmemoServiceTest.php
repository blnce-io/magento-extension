<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Balancepay\Balancepay\Model\Service\CreditmemoService;
use Magento\Sales\Model\Order\CreditmemoNotifier;
use Magento\Sales\Model\Order\RefundAdapterInterface;
use Magento\Sales\Api\CreditmemoCommentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order\Invoice;

class CreditmemoServiceTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @return void
     */
    public function testRefund()
    {
        $this->creditmemoInterface->expects($this->any())->method('setState')->willReturnSelf();
        $this->creditmemoInterface->expects($this->any())->method('getInvoice')->willReturn($this->invoice);
        $this->creditmemoInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->invoice->expects($this->any())->method('setIsUsedForRefund')->willReturnSelf();
        $this->invoice->expects($this->any())->method('setBaseTotalRefunded')->willReturnSelf();
        $this->expectException(LocalizedException::class);
        $result = $this->testableObject->refund($this->creditmemoInterface, false);
    }

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditmemoRepositoryInterface = $this->getMockBuilder(CreditmemoRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])->getMockForAbstractClass();

        $this->creditmemoCommentRepositoryInterface = $this->getMockBuilder(CreditmemoCommentRepositoryInterface::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();

        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();

        $this->creditmemoNotifier = $this->getMockBuilder(CreditmemoNotifier::class)
            ->disableOriginalConstructor()
            ->addMethods([])->onlyMethods([])->getMock();

        $this->priceCurrencyInterface = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])->onlyMethods([])->getMockForAbstractClass();

        $this->managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])->onlyMethods([])->getMockForAbstractClass();

        $this->creditmemoInterface = $this->getMockBuilder(CreditmemoInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setState'])->addMethods(['getInvoice', 'getId'])->getMockForAbstractClass();

        $this->invoice = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()->onlyMethods(['setBaseTotalRefunded', 'setIsUsedForRefund'])->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(CreditmemoService::class, [
            'creditmemoRepository' => $this->creditmemoRepositoryInterface,
            'creditmemoCommentRepository' => $this->creditmemoCommentRepositoryInterface,
            'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            'filterBuilder' => $this->filterBuilder,
            'creditmemoNotifier' => $this->creditmemoNotifier,
            'priceCurrency' => $this->priceCurrencyInterface,
            'eventManager' => $this->managerInterface
        ]);
    }
}
