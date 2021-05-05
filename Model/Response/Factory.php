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

/**
 * Balancepay response factory model.
 */
class Factory
{
    /**
     * Response handlers.
     */
    const CAPTURE_RESPONSE_HANDLER = 'capture';
    const CLOSE_RESPONSE_HANDLER = 'close';
    const CHECKOUT_RESPONSE_HANDLER = 'checkout';
    const WEBHOOKS_KEYS_RESPONSE_HANDLER = 'webhooks/keys';
    const WEBHOOKS_RESPONSE_HANDLER = 'webhooks';

    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        self::CAPTURE_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Capture::class,
        self::CLOSE_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Close::class,
        self::CHECKOUT_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Checkout::class,
        self::WEBHOOKS_KEYS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\WebhooksKeys::class,
        self::WEBHOOKS_RESPONSE_HANDLER => \Balancepay\Balancepay\Model\Response\Webhooks::class,
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
     * @param string            $type
     * @param Curl|null         $curl
     * @param OrderPayment|null $payment
     *
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
