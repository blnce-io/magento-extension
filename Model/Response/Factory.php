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

namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Lib\Http\Client\Curl;
use Balancepay\Balancepay\Model\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class Factory
{
    /**
     * Response handlers.
     */
    public const CAPTURE_RESPONSE_HANDLER = 'capture';
    public const CLOSE_RESPONSE_HANDLER = 'close';
    public const CHECKOUT_RESPONSE_HANDLER = 'checkout';
    public const TRANSACTIONS_RESPONSE_HANDLER = 'transactions';
    public const WEBHOOKS_KEYS_RESPONSE_HANDLER = 'webhooks/keys';
    public const WEBHOOKS_RESPONSE_HANDLER = 'webhooks';
    public const VENDORS_RESPONSE_HANDLER = 'vendors';
    public const BUYERS_RESPONSE_HANDLER = 'buyers';

    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        self::CAPTURE_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Transactions\Capture::class,
        self::CLOSE_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Transactions\Close::class,
        self::CHECKOUT_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Checkout::class,
        self::TRANSACTIONS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Transactions::class,
        self::WEBHOOKS_KEYS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Webhooks\Keys::class,
        self::WEBHOOKS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Webhooks::class,
        self::VENDORS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Vendors::class,
        self::BUYERS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Buyers::class,
    ];

    /**
     * Object manager object.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create response model.
     *
     * @param mixed $type
     * @param mixed $curl
     * @return ResponseInterface
     * @throws LocalizedException
     */
    public function create(
        $type,
        $curl = null
    ) {
        $className = !empty($this->invokableClasses[$type])
            ? $this->invokableClasses[$type]
            : null;

        if ($className === null) {
            throw new LocalizedException(
                __('%1 type is not supported.')
            );
        }

        $model = $this->objectManager->create(
            $className,
            [
                'curl' => $curl
            ]
        );
        if (!$model instanceof ResponseInterface) {
            throw new LocalizedException(
                __(
                    '%1 doesn\'t implement \Balancepay\Balancepay\Model\ResponseInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
