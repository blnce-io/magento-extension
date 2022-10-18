<?php
declare(strict_types=1);

namespace Balancepay\Balancepay\Test\Unit\Model\Request;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Balancepay\Balancepay\Model\Request\Refunds;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Balancepay\Balancepay\Model\Config;
use Magento\Directory\Model\RegionFactory;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\Response\Factory as ResponseFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Cart\CartTotalRepository;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Balancepay\Balancepay\Model\BalanceBuyer;

class RefundTest extends TestCase
{
    /**
     * Object for test
     *
     * @var object
     */
    private $testableObject;
    /**
     * @var Config|MockObject
     */
    private $balancepayConfig;
    /**
     * @var Curl|MockObject
     */
    private $curl;
    /**
     * @var Address|MockObject
     */
    private $address;
    /**
     * @var CustomerInterface|MockObject
     */
    private $customerInterface;
    /**
     * @var ResponseFactory|MockObject
     */
    private $responseFactory;
    /**
     * @var Item|MockObject
     */
    private $item;
    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;
    /**
     * @var Quote|MockObject
     */
    private $quote;
    /**
     * @var CartTotalRepository|MockObject
     */
    private $cartTotalRepository;
    /**
     * @var Product|MockObject
     */
    private $product;
    /**
     * @var HelperData|MockObject
     */
    private $helperData;
    /**
     * @var Customer|MockObject
     */
    private $customer;
    /**
     * @var AccountManagementInterface|MockObject
     */
    private $accountManagementInterface;
    /**
     * @var RegionFactory|MockObject
     */
    private $regionFactory;
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryInterface;
    /**
     * @var Session|MockObject
     */
    private $session;
    /**
     * @var BalanceBuyer|MockObject
     */
    private $balanceBuyer;
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    protected function setUp(): void
    {
        $this->balancepayConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()->getMock();

        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()->getMock();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()->addMethods(['__toArray'])->getMockForAbstractClass();

        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()->addMethods(['getIsVirtual', 'getBaseTaxAmount'])
            ->onlyMethods(['getProduct', 'getProductType', 'getChildren', 'getName', 'getQty', 'getSku'])->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseCurrencyCode', 'getCustomerEmail', 'getIterator'])
            ->onlyMethods(['collectTotals', 'getShippingAddress', 'getBillingAddress', 'getAllVisibleItems'])
            ->getMock();

        $this->cartTotalRepository = $this->getMockBuilder(CartTotalRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()->getMock();

        $this->helperData = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()->getMock();

        $this->accountManagementInterface = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()->onlyMethods(['getCustomer', 'isLoggedIn'])->getMockForAbstractClass();

        $this->balanceBuyer = $this->getMockBuilder(BalanceBuyer::class)
            ->disableOriginalConstructor()->onlyMethods(['getCustomerBalanceBuyerId'])->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->address = $this->getMockBuilder(\Magento\Customer\Model\Address::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->testableObject = $objectManager->getObject(Refunds::class, [
            'balancepayConfig' => $this->balancepayConfig,
            'curl' => $this->curl,
            'responseFactory' => $this->responseFactory,
            'helper' => $this->helperData,
            'accountManagement' => $this->accountManagementInterface,
            'region' => $this->regionFactory,
            'request' => $this->request,
            'address' => $this->address,
            'session' => $this->session
        ]);
    }

    public function testSetTopic()
    {
        $result = $this->testableObject->setTopic('topic');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testGetRequestMethod()
    {
        $result = $this->testableObject->getRequestMethod();
    }

    public function testGetAmount()
    {
        $result = $this->testableObject->getAmount();
    }

    public function testGetChargeId()
    {
        $result = $this->testableObject->getChargeId();
    }

    public function testGetReason()
    {
        $result = $this->testableObject->getReason();
    }

    public function testSetRequestMethod()
    {
        $result = $this->testableObject->setRequestMethod('requestmethod');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testSetReason()
    {
        $result = $this->testableObject->setReason('requestmethod');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testSetChargeId()
    {
        $result = $this->testableObject->setChargeId('1');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testSetAmount()
    {
        $result = $this->testableObject->setAmount('30');
        $this->assertEquals($this->testableObject, $result);
    }

    public function testGetTopic()
    {
        $result = $this->testableObject->getTopic();
    }
}
