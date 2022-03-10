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
     * Charged constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param BalancepayConfig $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param Json $json
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        Json $json,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->json = $json;
        $this->orderFactory = $orderFactory;
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
        sleep(15);// phpcs:ignore
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
                throw new LocalizedException(new Phrase("Signature is doesn't match!"));
            }

            //Prepare & validate params:
            $params = (array)$this->json->unserialize($content);
            $this->validateParams($params);
            $externalReferenceId = (string)$params['externalReferenceId'];
            $chargeId = (string)$params['chargeId'];
            $amount = (float)$params['amount'];

            //Load the order:
            $order = $this->orderFactory->create()->loadByIncrementId($externalReferenceId);

            if (!$order || !$order->getId()) {
                throw new LocalizedException(new Phrase("No matching order!"));
            }

            $orderPayment = $order->getPayment();

            //Process if needed:
            if (\strpos($orderPayment
                    ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID), $chargeId) === false) {
                if (!$orderPayment
                        ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT)
                    && round((float)$order->getBaseGrandTotal()) !== round($amount)) {
                    $orderPayment->setIsFraudDetected(true)->save();
                    $order->setStatus(Order::STATUS_FRAUD)->save();
                    throw new LocalizedException(new Phrase("The charged amount doesn't match the order total!"));
                }

                $orderPayment
                    ->setTransactionId($orderPayment
                        ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHECKOUT_TRANSACTION_ID))
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(true)
                    ->setAdditionalInformation(
                        BalancepayMethod::BALANCEPAY_CHARGE_ID,
                        $orderPayment->getAdditionalInformation(
                            BalancepayMethod::BALANCEPAY_CHARGE_ID,
                            $chargeId
                        ) . " \n" . $chargeId
                    );

                if (!$orderPayment
                    ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_AUTH_CHECKOUT)) {
                    $orderPayment->capture(null);
                }

                $orderPayment->save();
                $order->save();
            } elseif ($chargeId !== (string)$order->getPayment()
                    ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID)) {
                throw new LocalizedException(new Phrase("Charge ID mismatch!"));
            }

            $resBody = [
                "error" => 0,
                "message" => "Success",
                "order" => $order->getIncrementId()
            ];
        } catch (\Exception $e) {
            $this->balancepayConfig
                ->log('Webhook\Checkout\Charged::execute() [Exception: ' .
                    $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
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
