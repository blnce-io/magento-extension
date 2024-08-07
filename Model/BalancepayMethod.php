<?php
/**
 * Balance Payments For Magento 2
 * https://www.getbalance.com/
 *
 * @category Balance
 * @package  Balancepay_Balancepay
 * @author   Developer: Pniel Cohen
 * @author   Company: Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Helper\Data as HelperData;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger as PaymentMethodLogger;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Balancepay payment model.
 */
class BalancepayMethod extends AbstractMethod
{
    /**
     * Method code const.
     */
    public const METHOD_CODE = 'balancepay';

    /**
     * Modes.
     */
    public const MODE_SANDBOX = 'sandbox';
    public const MODE_LIVE = 'live';

    public const BALANCEPAY_CHECKOUT_TOKEN = 'balancepay_checkout_token';
    public const BALANCEPAY_CHECKOUT_TRANSACTION_ID = 'balancepay_checkout_transaction_id';
    public const BALANCEPAY_CHARGE_ID = 'balancepay_charge_id';
    public const BALANCEPAY_IS_AUTH_CHECKOUT = 'balancepay_is_auth_checkout';
    public const BALANCEPAY_IS_FINANCED = 'balancepay_is_financed';
    public const BALANCEPAY_SELECTED_PAYMENT_METHOD= 'balancepay_selected_payment_method';

    /**
     * Gateway code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = \Balancepay\Balancepay\Block\Info::class;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var BalancepayConfig
     */
    protected $balancepayConfig;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $customerSession;

    protected $registry;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentDataHelper $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentMethodLogger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param DirectoryHelper|null $directory
     * @param CheckoutSession $checkoutSession
     * @param Config $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param RequestInterface $request
     * @param HelperData $helper
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentDataHelper $paymentData,
        ScopeConfigInterface $scopeConfig,
        PaymentMethodLogger $logger,
        CheckoutSession $checkoutSession,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        RequestInterface $request,
        HelperData $helper,
        Session $customerSession,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        DirectoryHelper $directory = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->request = $request;
        $this->helper = $helper;
        $this->registry = $registry;
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isMultipleShippingAddresses = ($quote && $quote->isMultipleShippingAddresses());
        $isCustomerGroupAllowed = $this->helper->isCustomerGroupAllowed();
        if ($isMultipleShippingAddresses || !$isCustomerGroupAllowed) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * Assign data.
     *
     * @param DataObject $data Data object.
     *
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        return $this;
    }

    /**
     * Validate payment method information object.
     *
     * @return Gateway
     * @throws LocalizedException
     */
    public function validate()
    {
        $info = $this->getInfoInstance();

        return $this;
    }

    /**
     * Check if payment method can be used for provided currency.
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * Get config payment action url.
     *
     * Used to universalize payment actions when processing payment place.
     *
     * @return string
     * @api
     * @deprecated 100.2.0
     */
    public function getConfigPaymentAction()
    {
        if ($this->balancepayConfig->getIsAuth()) {
            return \Magento\Payment\Model\MethodInterface::ACTION_AUTHORIZE;
        }
        return \Magento\Payment\Model\MethodInterface::ACTION_ORDER;
    }

    /**
     * Order payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function order(InfoInterface $payment, $amount)
    {
        parent::order($payment, $amount);

        $payment->setAdditionalInformation(
            self::BALANCEPAY_CHECKOUT_TOKEN,
            $this->checkoutSession->getBalanceCheckoutToken()
        );
        $payment->setAdditionalInformation(
            self::BALANCEPAY_CHECKOUT_TRANSACTION_ID,
            $this->checkoutSession->getBalanceCheckoutTransactionId()
        );
        $payment->setIsTransactionPending(true);

        return $this;
    }

    /**
     * Authorize payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $payment->setAdditionalInformation(
            self::BALANCEPAY_CHECKOUT_TOKEN,
            $this->checkoutSession->getBalanceCheckoutToken()
        );
        $payment->setAdditionalInformation(
            self::BALANCEPAY_CHECKOUT_TRANSACTION_ID,
            $this->checkoutSession->getBalanceCheckoutTransactionId()
        );
        $payment->setAdditionalInformation(self::BALANCEPAY_IS_AUTH_CHECKOUT, 1);

        return $this;
    }

    /**
     * Capture payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function capture(InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);

        if ($payment->getAdditionalInformation(self::BALANCEPAY_IS_AUTH_CHECKOUT)) {
            $invoiceData = $this->request->getParam('invoice', []);
            $invoiceItems = isset($invoiceData['items']) ? $invoiceData['items'] : [];
            $orderItems = $payment->getOrder()->getItems();
            $balanceVendorId = null;

            foreach ($orderItems as $item) {
                $_VendorIdBySellerProduct = $this->helper->getBalanceVendors($item->getProductId());
                if ($item->getProductType() === 'configurable' && $item->getHasChildren()) {
                    foreach ($item->getChildrenItems() as $child) {
                        $child->getProduct()->load($child->getProductId());
                        if (!$_VendorIdBySellerProduct) {
                            $_VendorIdBySellerProduct = $this->helper->getBalanceVendors($child->getProductId());
                        }
                    }
                }
                if ($_VendorIdBySellerProduct) {
                    if ($balanceVendorId && $balanceVendorId !== $_VendorIdBySellerProduct) {
                        throw new LocalizedException(
                            __('Invoicing items from different Balance vendors on one invoice is not allowed.
                            Please cleate a separate invoice for each')
                        );
                    }
                    $balanceVendorId = $_VendorIdBySellerProduct;
                }
            }

            $response = $this->requestFactory
                ->create(RequestFactory::CAPTURE_REQUEST_METHOD)
                ->setPayment($payment)
                ->setAmount($amount)
                ->setBalanceVendorId($balanceVendorId)
                ->process();

            $charges = $response['charges'];
            if (is_array($charges) && isset($charges[0])) {
                $chargeId = $charges[0]['id'];
                $status = $charges[0]['status'];
                $this->registry->register('charge_id', $chargeId);
                $this->registry->register('charge_status', $status);
            }
        }
        return $this;
    }

    /**
     * Refund payment method.
     *
     * @param InfoInterface $payment
     *
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function void(InfoInterface $payment)
    {
        parent::void($payment);

        if ($payment->getAdditionalInformation(self::BALANCEPAY_IS_AUTH_CHECKOUT)) {
            $this->requestFactory
                ->create(RequestFactory::CLOSE_REQUEST_METHOD)
                ->setPayment($payment)
                ->process();
        }

        return $this;
    }
}
