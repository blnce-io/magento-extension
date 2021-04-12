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

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;

/**
 * Balancepay config provider model.
 */
class ConfigProvider extends CcGenericConfigProvider
{
    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @method __construct
     * @param  CcConfig                        $ccConfig
     * @param  PaymentHelper                   $paymentHelper
     * @param  Config                          $balancepayConfig
     * @param  CheckoutSession                 $checkoutSession
     * @param  array                           $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Config $balancepayConfig,
        CheckoutSession $checkoutSession,
        array $methodCodes
    ) {
        $methodCodes = array_merge_recursive(
            $methodCodes,
            [BalancepayMethod::METHOD_CODE]
        );
        parent::__construct(
            $ccConfig,
            $paymentHelper,
            $methodCodes
        );
        $this->balancepayConfig = $balancepayConfig;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $this->balancepayConfig->getUrlBuilder();
    }
    /**
     * Return config array.
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->balancepayConfig->isActive()) {
            return [];
        }

        $this->checkoutSession->unsBalanceCustomerEmail();
        $this->checkoutSession->unsBalanceCheckoutToken();

        $config = [
            'payment' => [
                BalancepayMethod::METHOD_CODE => [
                    'balanceSdkUrl' => $this->balancepayConfig->getBalanceSdkUrl(),
                    'balanceIframeUrl' => $this->balancepayConfig->getBalanceIframeUrl(),
                    'balanceCheckoutTokenUrl' => $this->urlBuilder->getUrl('balancepay/payment_checkout/token'),
                    'balancelogoImageUrl' => $this->balancepayConfig->getLogoImageUrl(),
                    'balanceIsAuth' => $this->balancepayConfig->getIsAuth(),
                ],
            ],
        ];

        return $config;
    }
}
