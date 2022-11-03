<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Block\Info;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\State;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayCharge\Collection;

class InfoTest extends TestCase
{
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Info::class, [
            'context' => $this->context,
            'appState' => $this->state,
            'collection' => $this->collection,
            'data' => []
        ]);
    }

    public function testGetSpecificInformation()
    {
        $result = $this->testableObject->getSpecificInformation();
    }
}









