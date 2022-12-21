<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\ConfirmedProcessor;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class ConfirmedProcessorTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Phrase|MockObject
     */
    private $phrase;

    /**
     * @return void
     */
    public function testProcessConfirmedWebhook()
    {
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();

        $this->orderPaymentInterface->expects($this->exactly(3))->method('setAdditionalInformation')
            ->withConsecutive(
                [BalancepayMethod::BALANCEPAY_IS_FINANCED, 1],
                [BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT, 1],
                [BalancepayMethod::BALANCEPAY_SELECTED_PAYMENT_METHOD, '0.0']
            )->willReturnOnConsecutiveCalls(
                $this->orderPaymentInterface,
                $this->orderPaymentInterface,
                $this->orderPaymentInterface
            );

        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->balancepayConfig->expects($this->any())->method('getIsAuth')->willReturn(1);
        $result = $this->testableObject->processConfirmedWebhook(
            [
                'isFinanced' => 1,
                'selectedPaymentMethod' => 'RnGw68WL1qFDKJJJ5Qnnhn38dVrEejcRGdJvA'
            ],
            $this->order
        );
        $this->assertIsBool($result);
    }

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIsAuth'])->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()->onlyMethods(['save', 'getPayment'])->getMock();

        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['save'])->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(ConfirmedProcessor::class, [
            'balancepayConfig' => $this->balancepayConfig
        ]);
    }
}
