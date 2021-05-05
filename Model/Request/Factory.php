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

namespace Balancepay\Balancepay\Model\Request;

use Balancepay\Balancepay\Model\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Balancepay request factory model.
 */
class Factory
{

    /**
     * Request methods.
     */
    const CAPTURE_REQUEST_METHOD = 'capture';
    const CHECKOUT_REQUEST_METHOD = 'checkout';
    const WEBHOOKS_KEYS_REQUEST_METHOD = 'webhooks/keys';
    const WEBHOOKS_REQUEST_METHOD = 'webhooks';

    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        self::CAPTURE_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Capture::class,
        self::CHECKOUT_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Checkout::class,
        self::WEBHOOKS_KEYS_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\WebhooksKeys::class,
        self::WEBHOOKS_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Webhooks::class,
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
     * Create request model.
     *
     * @param string       $method
     *
     * @return RequestInterface
     * @throws LocalizedException
     */
    public function create($method)
    {
        $className = !empty($this->invokableClasses[$method])
            ? $this->invokableClasses[$method]
            : null;

        if ($className === null) {
            throw new LocalizedException(
                __('%1 method is not supported.')
            );
        }

        $model = $this->objectManager->create($className);

        if (!$model instanceof RequestInterface) {
            throw new LocalizedException(
                __(
                    '%1 doesn\'t implement \Balancepay\Balancepay\Model\RequestInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
