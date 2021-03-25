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

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger as PaymentMethodLogger;

/**
 * Balancepay payment model.
 */
class BalancepayMethod extends AbstractMethod
{
    /**
     * Method code const.
     */
    const METHOD_CODE = 'balancepay';

    /**
     * Modes.
     */
    const MODE_SANDBOX = 'sandbox';
    const MODE_LIVE = 'live';

    const BALANCEPAY_CHECKOUT_TOKEN = 'balancepay_checkout_token';
    const BALANCEPAY_CHARGE_ID = 'balancepay_charge_id';

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
    protected $_canAuthorize = false;

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
    protected $_canCapturePartial = false;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canVoid = false;

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
     * @method __construct
     * @param  Context                    $context
     * @param  Registry                   $registry
     * @param  ExtensionAttributesFactory $extensionFactory
     * @param  AttributeValueFactory      $customAttributeFactory
     * @param  PaymentDataHelper          $paymentData
     * @param  ScopeConfigInterface       $scopeConfig
     * @param  PaymentMethodLogger        $logger
     * @param  AbstractResource           $resource
     * @param  AbstractDb                 $resourceCollection
     * @param  array                      $data
     * @param  DirectoryHelper            $directory
     * @param  CheckoutSession            $checkoutSession
     * @param  BalancepayConfig           $balancepayConfig
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentDataHelper $paymentData,
        ScopeConfigInterface $scopeConfig,
        PaymentMethodLogger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null,
        CheckoutSession $checkoutSession,
        BalancepayConfig $balancepayConfig
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

        $this->checkoutSession = $checkoutSession;
        $this->balancepayConfig = $balancepayConfig;
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

        $payment->setAdditionalInformation(self::BALANCEPAY_CHECKOUT_TOKEN, $this->checkoutSession->getBalanceCheckoutToken());
        $payment->setIsTransactionPending(true);

        return $this;
    }
}
