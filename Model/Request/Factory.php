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
    const CLOSE_REQUEST_METHOD = 'close';
    const CHECKOUT_REQUEST_METHOD = 'checkout';
    const TRANSACTIONS_REQUEST_METHOD = 'transactions';
    const WEBHOOKS_KEYS_REQUEST_METHOD = 'webhooks/keys';
    const WEBHOOKS_REQUEST_METHOD = 'webhooks';
    const VENDORS_REQUEST_METHOD = 'vendors';

    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        self::CAPTURE_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Transactions\Capture::class,
        self::CLOSE_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Transactions\Close::class,
        self::CHECKOUT_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Checkout::class,
        self::TRANSACTIONS_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Transactions::class,
        self::WEBHOOKS_KEYS_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Webhooks\Keys::class,
        self::WEBHOOKS_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Webhooks::class,
        self::VENDORS_REQUEST_METHOD => \Balancepay\Balancepay\Model\Request\Vendors::class,
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
