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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
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
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  JsonFactory            $jsonResultFactory
     * @param  BalancepayConfig       $balancepayConfig
     * @param  RequestFactory         $requestFactory
     * @param  Json                   $json
     * @param  OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        Json $json,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->json = $json;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }
    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        if (!$this->balancepayConfig->isActive()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $resBody = [];

        try {
            $content = $this->getRequest()->getContent();
            $headers = $this->getRequest()->getHeaders()->toArray();

            $this->balancepayConfig->log('Webhook\Checkout\Charged::execute() ', 'debug', [
                'content' => $content,
                'headers' => $headers,
            ]);

            //Validate Signature:
            $signature = hash_hmac("sha256", $content, $this->balancepayConfig->getWebhookSecret());
            if ($signature !== $headers['X-Blnce-Signature']) {
                throw new \Exception("Signature is doesn't match!");
            }

            //Prepare & validate params:
            $params = (array) $this->json->unserialize($content);
            $this->validateParams($params);
            $checkoutToken = (string) $params['checkoutToken'];
            $chargeId = (string) $params['chargeId'];
            $amount = (float) $params['amount'];

            //Load the order:
            $ordersCollection = $this->orderCollectionFactory->create();
            $ordersCollection->getSelect()->join(
                ['sop' => $ordersCollection->getTable('sales_order_payment')],
                "main_table.entity_id = sop.entity_id AND sop.method = '" . BalancepayMethod::METHOD_CODE . "'",
                []
            );
            $ordersCollection->addAttributeToFilter('sop.additional_information', ['like' => '%' . $checkoutToken . '%']);
            $ordersCollection->setPageSize(1);

            $order = $ordersCollection->getFirstItem();

            if (!$order || !$order->getId()) {
                throw new \Exception("No matching order!");
            }

            $orderPayment = $order->getPayment();

            //Process if needed:
            if (\strpos($orderPayment->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID), $chargeId) === false) {
                if (round((float)$order->getBaseGrandTotal()) !== round($amount)) {
                    $orderPayment->setIsFraudDetected(true)->save();
                    $order->setStatus(Order::STATUS_FRAUD)->save();
                    throw new \Exception("The charged amount doesn't match the order total!");
                }

                $orderPayment
                    ->setTransactionId($orderPayment->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID))
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(true)
                    ->setAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID, $orderPayment->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID, $chargeId) . " \n" . $chargeId);

                if (!$orderPayment->getAdditionalInformation(self::BALANCEPAY_IS_AUTH_CHECKOUT)) {
                    $orderPayment->capture(null);
                }

                $orderPayment->save();
                $order->save();
            } elseif ($chargeId !== (string) $order->getPayment()->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID)) {
                throw new \Exception("Charge ID mismatch!");
            }

            $resBody = [
                "error" => 0,
                "message" => "Success",
                "order" => $order->getIncrementId()
            ];
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Webhook\Checkout\Charged::execute() [Exception: ' . $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
            $resBody = [
                "error" => 1,
                "message" => $e->getMessage(),
            ];
            if ($this->balancepayConfig->isDebugEnabled()) {
                $resBody["trace"] = $e->getTraceAsString();
            }
        }

        return $this->jsonResultFactory->create()
            ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK)
            ->setData($resBody);
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function validateParams($params)
    {
        $requiredKeys = ['checkoutToken', 'chargeId', 'amount'];
        $bodyKeys = array_keys($params);

        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new Exception(
                __(
                    'Balancepay webhook required fields are missing: %1.',
                    implode(', ', $diff)
                )
            );
        }

        return $this;
    }

    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
