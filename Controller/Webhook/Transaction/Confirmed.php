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

namespace Balancepay\Balancepay\Controller\Webhook\Transaction;

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
use Symfony\Component\Console\Input\ArrayInputFactory;
use Balancepay\Balancepay\Helper\Data;

/**
 * Balancepay transaction/confirmed webhook.
 */
class Confirmed extends Action implements CsrfAwareActionInterface
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
     * Confirmed constructor.
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
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->balancepayConfig->isActive()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $newArr = json_decode('{"content":"{\"transactionId\":\"txn_6e4b4059b15497ede3a9579d\",\"isFinanced\":true,\"externalReferenceId\":\"000000045\",\"selectedPaymentMethod\":\"bank\",\"amount\":89,\"selectedPaymentMethodId\":\"8rVwr7PZpaiv9mGpjyeyTkBEj8DX3QHwZ5b8g\",\"eventTime\":\"2022-03-13T08:10:21.789Z\",\"topic\":\"transaction/confirmed\"}","headers":{"X-Varnish":"1347702","X-Forwarded-For":"3.129.82.43","Traceparent":"00-dfbadcc144757b3897b5d818292d67f5-b8c89623078ed6af-01","User-Agent":"axios/0.21.1","X-Balance-Signature":"4635ae38ba4bda9a59ac7d570e012039dd8125d59dfc143223a6d9a12c59c8d3","X-Blnce-Signature":"4635ae38ba4bda9a59ac7d570e012039dd8125d59dfc143223a6d9a12c59c8d3","X-Balance-Topic":"transaction/confirmed","X-Blnce-Topic":"transaction/confirmed","Content-Type":"application/json","Accept":"application/json, text/plain, */*","Content-Length":"280","X-Version":"241","X-App-User":"aaqmhgatpj","X-Application":"magento","X-Forwarded-Host":"magento.getbalance.com","X-Forwarded-Proto":"https","Host":"magento.getbalance.com","X-Real-Ip":"3.129.82.43"},"store_id":"1"}',true);
        //$content = json_decode($newArr['content'],true);
        $content = $newArr['content'];
        $headers = $newArr['headers'];

        // $content = $this->getRequest()->getContent();
        // $headers = $this->getRequest()->getHeaders()->toArray();
        $this->balancepayConfig->log('Webhook\Checkout\Confirmed::execute() ', 'debug', [
            'content' => $content,
            'headers' => $headers,
        ]);
        $this->helperData->getConfirmedData($content, $headers);
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
