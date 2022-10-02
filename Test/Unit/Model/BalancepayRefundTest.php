<?php

namespace Balancepay\Balancepay\Test\Unit\Model;

use Balancepay\Balancepay\Model\BalancepayRefund;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class BalancepayRefundTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;

    /**
     * This method is called before a test is executed
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->testableObject = (new ObjectManager($this))->getObject(BalancepayRefund::class);
    }
}
