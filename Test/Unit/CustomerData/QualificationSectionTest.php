<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\CustomerData;

use Balancepay\Balancepay\Helper\Data as BalancepayHelper;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Customer\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\CustomerData\QualificationSection;

class QualificationSectionTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerSession;

    /**
     * @var BalancepayConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $balancepayConfig;

    /**
     * @var BalancepayHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $balancepayHelper;

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()->getMock();

        $this->balancepayConfig = $this->getMockBuilder(BalancepayConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->balancepayHelper = $this->getMockBuilder(BalancepayHelper::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(QualificationSection::class, [
            'customerSession' => $this->customerSession,
            'balancepayConfig' => $this->balancepayConfig,
            'balancepayHelper' => $this->balancepayHelper
        ]);
    }

    /**
     * @return void
     */
    public function testGetSectionData()
    {
        $this->balancepayHelper->expects($this->any())->method('getBuyerAmount')->willReturn([]);
        $result = $this->testableObject->getSectionData();
        $this->assertIsArray($result);
    }

    public function testGetSectionDataWithData()
    {
        $this->balancepayHelper->expects($this->any())->method('getBuyerAmount')
            ->willReturn([
                'qualificationStatus' => true,
                'qualificationStatus' => 'completed',
                'qualification' => ['creditLimit' => 100]
            ]);
        $this->balancepayHelper->expects($this->any())->method('formattedAmount')->willReturn('5677');
        $result = $this->testableObject->getSectionData();
        $this->assertIsArray($result);
    }
}
