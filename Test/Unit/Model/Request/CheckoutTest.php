<?php

declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model\Request;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\Request\Checkout;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Cart\CartTotalRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Model\Request\Buyers;

class CheckoutTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;
    /**
     * @var CartTotalRepository|MockObject
     */
    private $cartTotalRepository;

    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()->getMock();

        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->helper = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()->getMock();

        $this->accountManagement = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->region = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->cartTotalRepository = $this->getMockBuilder(CartTotalRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Checkout::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'curl' => $this->curl,
            'responseFactory' => $this->responseFactory,
            'helper' => $this->helper,
            'accountManagement' => $this->accountManagement,
            'cartTotalRepository' => $this->cartTotalRepository,
            'region' => $this->region,
            'checkoutSession' => $this->checkoutSession
        ]);
    }
}
