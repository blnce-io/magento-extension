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

namespace Balancepay\Balancepay\Controller\Webhook\Checkout;

use Balancepay\Balancepay\Helper\Data;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Balancepay checkout/charged webhook.
 */
class Charged extends Action implements CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var BalancepayConfig
     */
    private $balancepayConfig;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * Charged constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param BalancepayConfig $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param Json $json
     * @param OrderFactory $orderFactory
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        Json $json,
        OrderFactory $orderFactory,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->json = $json;
        $this->orderFactory = $orderFactory;
        $this->helperData = $helperData;
    }

    /**
     * Execute
     *
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        if (!$this->balancepayConfig->isActive()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }
            $content = $this->getRequest()->getContent();
            $headers = $this->getRequest()->getHeaders()->toArray();
             $this->helperData->getChargedData($content, $headers);
    }

    /**
     * ValidateParams
     *
     * @param array|string $params
     * @return $this
     */
    private function validateParams($params)
    {
        $requiredKeys = ['externalReferenceId', 'chargeId', 'amount'];
        $bodyKeys = array_keys($params);

        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new LocalizedException(
                new Phrase(
                    'Balancepay webhook required fields are missing: %1.',
                    [implode(', ', $diff)]
                )
            );
        }

        return $this;
    }

    /**
     * CreateCsrfValidationException
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * ValidateForCsrf
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
