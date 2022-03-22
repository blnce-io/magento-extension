<?php

namespace Balancepay\Balancepay\Helper;

use Balancepay\Balancepay\Controller\Webhook\Checkout\Charged;
use Balancepay\Balancepay\Controller\Webhook\Transaction\Confirmed;
use Balancepay\Balancepay\Model\BalancepayMethod;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\ResourceModel\BalancepayProduct\CollectionFactory as MpProductCollection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Response;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Balancepay\Balancepay\Model\WebhookFactory;

class Data extends AbstractHelper
{
    /**
     * @var MpProductCollection
     */
    protected $_mpProductCollectionFactory;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var Context
     */
    protected $appContext;

    /**
     * @var OrderFactory
     */
    private $orderFactory;


    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var BalancepayConfig
     */
    protected $balancepayConfig;

    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @var string[]
     */
    public $ccIcons;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * Data constructor.
     *
     * @param MpProductCollection $mpProductCollectionFactory
     * @param TypeListInterface $cacheTypeList
     * @param MessageManagerInterface $messageManager
     * @param Context $appContext
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param RequestFactory $requestFactory
     * @param BalancepayConfig $balancepayConfig
     * @param PricingHelper $pricingHelper
     * @param Json $json
     * @param OrderFactory $orderFactory
     * @param WebhookFactory $webhookFactory
     * @param JsonFactory $jsonResultFactory
     */
    public function __construct(
        MpProductCollection $mpProductCollectionFactory,
        TypeListInterface $cacheTypeList,
        MessageManagerInterface $messageManager,
        Context $appContext,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        RequestFactory $requestFactory,
        BalancepayConfig $balancepayConfig,
        PricingHelper $pricingHelper,
        Json $json,
        OrderFactory $orderFactory,
        WebhookFactory $webhookFactory,
        JsonFactory $jsonResultFactory
    )
    {
        $this->ccIcons = [
            'visa' => 'vi',
            'discover' => 'di',
            'mastercard' => 'mc',
            'maestro' => 'mi'
        ];
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->cacheTypeList = $cacheTypeList;
        $this->messageManager = $messageManager;
        $this->appContext = $appContext;
        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->requestFactory = $requestFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->pricingHelper = $pricingHelper;
        $this->json = $json;
        $this->orderFactory = $orderFactory;
        $this->webhookFactory = $webhookFactory;
    }

    /**
     * Get balance Vendors
     *
     * @param string $productId
     * @return string
     */
    public function getBalanceVendors($productId = '')
    {
        return $this->getSellerIdByProductId($productId);
    }

    /**
     * Return the seller Id by product id.
     *
     * @param string $productId
     * @return mixed
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $sellerId = $collection->getFirstItem()->getVendorId();
        return $sellerId;
    }

    /**
     * @param $content
     * @param $headers
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processWebhook($content, $headers, $webhookName)
    {
        $resBody = [];
        try {
            $params = $this->validateSignature($content, $headers);
            $externalReferenceId = (string)$params['externalReferenceId'];
            $order = $this->orderFactory->create()->loadByIncrementId($externalReferenceId);

            if (!$order || !$order->getId()) {
                $this->addWebhookQue($params, $webhookName);
                throw new LocalizedException(new Phrase("No matching order!"));
            }

            if ($webhookName == Confirmed::WEBHOOK_CONFIRMED_NAME) {
                $this->processConfirmedWebhook($params, $order);
            } elseif ($webhookName == Charged::WEBHOOK_CHARGED_NAME) {
                $this->processChargedWebhook($params, $order);
            }

            $resBody = [
                "error" => 0,
                "message" => "Success",
                "order" => $order->getIncrementId()
            ];
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Webhook
            [Exception: ' . $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
            $resBody = [
                "error" => 1,
                "message" => $e->getMessage(),
            ];
            if ($this->balancepayConfig->isDebugEnabled()) {
                $resBody["trace"] = $e->getTraceAsString();
            }
        }

        return $this->jsonResultFactory->create()
            ->setHttpResponseCode(Response::HTTP_OK)
            ->setData($resBody);
    }

    public function addWebhookQue($params, $name)
    {
        $webhookModel = $this->webhookFactory->create();
        $webhookModel->setData([
            'payload' => $this->json->serialize($params),
            'name' => $name,
            'attempts' => 1
        ]);
        $webhookModel->save();
    }

    /**
     * @param $params
     * @param $order
     */
    public function processConfirmedWebhook($params, $order)
    {
        try {
            $isFinanced = $params['isFinanced'] ? 1 : 0;
            $selectedPaymentMethod = (float)$params['selectedPaymentMethod'];
            $orderPayment = $order->getPayment();
            $orderPayment
                ->setAdditionalInformation(BalancepayMethod::BALANCEPAY_IS_FINANCED, $isFinanced)
                ->setAdditionalInformation(BalancepayMethod::
                BALANCEPAY_SELECTED_PAYMENT_METHOD, $selectedPaymentMethod);
            $orderPayment->save();
            $order->save();
            return true;
        } catch (\Exception $e) {
            $this->balancepayConfig->log($e->getMessage());
        }
    }

    /**
     * @param $params
     * @param $order
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processChargedWebhook($params, $order)
    {
        try {
            $chargeId = (string)$params['chargeId'];
            $amount = (float)$params['amount'];
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
                return true;
            } elseif ($chargeId !== (string)$order->getPayment()
                    ->getAdditionalInformation(BalancepayMethod::BALANCEPAY_CHARGE_ID)) {
                throw new LocalizedException(new Phrase("Charge ID mismatch!"));
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log($e->getMessage());
            return false;
        }
    }

    /**
     * @param $content
     * @return array
     * @throws LocalizedException
     */
    public function validateSignature($content, $headers): array
    {
        //Validate Signature:
       /* $signature = hash_hmac("sha256", $content, $this->balancepayConfig->getWebhookSecret());
        if ($signature !== $headers['X-Blnce-Signature']) {
            throw new LocalizedException(new Phrase("Signature is doesn't match!"));
        }*/
        //Prepare & validate params:
        $params = (array)$this->json->unserialize($content);
        $this->validateParams($params);
        return $params;
    }

    /**
     * @param $content
     * @param $headers
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkoutProcess($content, $headers)
    {
        $resBody = [];
        try {
            $params = $this->validateSignature($content, $headers);
            $externalReferenceId = (string)$params['externalReferenceId'];

            //Load the order:
            $order = $this->orderFactory->create()->loadByIncrementId($externalReferenceId);

            if (!$order || !$order->getId()) {
                $this->addWebhookQue($params, 'checkout/charged');
                throw new LocalizedException(new Phrase("No matching order!"));
            }
            $this->processChargedWebhook($params, $order);
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
            ->setHttpResponseCode(Response::HTTP_OK)
            ->setData($resBody);
    }

    /**
     * GetCustomerSessionId
     *
     * @return mixed
     */
    public function getCustomerSessionId()
    {
        return $this->appContext->getValue('customer_id');
    }

    /**
     * UpdateAttempts
     *
     * @param $id
     * @param $field
     * @param $value
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateAttempts($id, $field, $value): bool
    {
        try {
            $webhookModel = $this->webhookFactory->create()->load($id, 'entity_id');
            $entityId = $webhookModel->getEntityId();
            $webhookModel->setData([
                'entity_id' => $entityId,
                $field => $value
            ])->save();
        } catch (\Exception $e) {
            $this->balancepayConfig->log($e->getMessage());
        }
        return true;
    }

    /**
     * GetBuyerAmount
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBuyerAmount()
    {
        $response = [];
        try {
            $buyerId = $this->getCustomerSessionBuyerId();
            if (!empty($buyerId)) {
                $response = $this->requestFactory
                    ->create(RequestFactory::BUYER_REQUEST_METHOD)
                    ->setRequestMethod('buyers/' . $buyerId)
                    ->setTopic('getbuyers')
                    ->process();
            }
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Get Buyer [Exception: ' .
                $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
        }
        return $response;
    }

    /**
     * GetCustomerSessionBuyerId
     *
     * @return mixed
     */
    public function getCustomerSessionBuyerId()
    {
        if ($this->customerSession->getBuyerId()) {
            return $this->customerSession->getBuyerId();
        }
        $customerId = $this->getCustomerSessionId();
        if (!empty($customerId)) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $customerAttributeData = $customer->__toArray();
            $buyerId = isset($customerAttributeData['custom_attributes']['buyer_id']) ?
                $customerAttributeData['custom_attributes']['buyer_id']['value'] : '';
            $this->customerSession->setBuyerId($buyerId);
            return $buyerId;
        }
        return 0;
    }

    /**
     * FormattedAmount
     *
     * @param float|string $price
     * @return float|string
     */
    public function formattedAmount($price)
    {
        return $this->pricingHelper->currency($price / 100, true, false);
    }

    /**
     * ValidateParams
     *
     * @param string|Array $params
     * @return $this
     */
    private function validateParams($params)
    {
        $requiredKeys = ['externalReferenceId', 'isFinanced', 'selectedPaymentMethod'];
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
     * IsCustomerGroupAllowed
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCustomerGroupAllowed()
    {
        $currentCustomerGroup = $this->customerSession->getCustomer()->getGroupId();
        $allowedCustomerGroups = $this->balancepayConfig->getAllowedCustomerGroups();
        return in_array($currentCustomerGroup, $allowedCustomerGroups);
    }

    /**
     * @param $params
     * @param $webhook
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processWebhookCron($params, $webhook)
    {
        $isTransactionSuccess = false;
        //Load the order:
        $order = $this->orderFactory->create()->loadByIncrementId((string)$params['externalReferenceId']);
        if (!$order || !$order->getId()) {
            //update attempt in webhook queue
            $attempts = $webhook->getAttempts() + 1;
            $this->updateAttempts($webhook->getEntityId(), 'attempts', $attempts);
            throw new LocalizedException(new Phrase("No matching order!"));
        }
        if ($webhook->getName() == Confirmed::WEBHOOK_CONFIRMED_NAME) {
            $isTransactionSuccess = $this->processConfirmedWebhook($params, $order);
        } elseif ($webhook->getName() == Charged::WEBHOOK_CHARGED_NAME) {
            $isTransactionSuccess = $this->processChargedWebhook($params, $order);
        }
        if ($isTransactionSuccess) {
            $webhook->delete();
        }
    }
}
