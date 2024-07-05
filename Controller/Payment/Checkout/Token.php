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

namespace Balancepay\Balancepay\Controller\Payment\Checkout;

use Balancepay\Balancepay\Model\BalanceBuyer;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Webapi\Response;

/**
 * Balancepay get checkout token.
 */
class Token extends Action
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
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var BalanceBuyer
     */
    private $balanceBuyer;
    
    private $context;

    private $_coreSession;

    /**
     * Token constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param BalancepayConfig $balancepayConfig
     * @param RequestFactory $requestFactory
     * @param CheckoutSession $checkoutSession
     * @param Session $customerSession
     * @param BalanceBuyer $balanceBuyer
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BalancepayConfig $balancepayConfig,
        RequestFactory $requestFactory,
        CheckoutSession $checkoutSession,
        Session $customerSession,
        BalanceBuyer $balanceBuyer,
        SessionManagerInterface $coreSession
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->requestFactory = $requestFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->balanceBuyer = $balanceBuyer;
        $this->_coreSession = $coreSession;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        if (!$this->balancepayConfig->isActive()) {
            return $this->context->getResultFactory()->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $resBody = [];

        try {
            $params = $this->getRequest()->getParams();
            if (isset($params['email']) && $params['email']) {
                $this->checkoutSession->setBalanceCustomerEmail($params['email']);
            }
            $this->checkoutSession->unsBalanceCheckoutToken();

            $params = $this->getRequest()->getParams();
            $fallbackEmail = (isset($params['email']) && $params['email']) ? $params['email'] : null;

            $result = $this->requestFactory
                ->create(RequestFactory::TRANSACTIONS_REQUEST_METHOD)
                ->setRequestMethod('transactions')
                ->setFallbackEmail($fallbackEmail)
                ->process();
            $token = $result->getToken();
            $transactionId = $result->getTransactionId();
            $buyerId = $result->getBuyerId();

            if ($buyerId &&
                $this->customerSession->isLoggedIn() &&
                empty($this->balanceBuyer->getCustomerBalanceBuyerId())
            ) {
                $this->balanceBuyer->updateCustomerBalanceBuyerId($buyerId);
            }

            if ($buyerId && !$this->customerSession->isLoggedIn()) {
                $this->_coreSession->start();
                $this->_coreSession->setBalanceBuyerId($buyerId);
            }
            $this->checkoutSession->setBalanceCheckoutToken($token);
            $this->checkoutSession->setBalanceCheckoutTransactionId($transactionId);
            $resBody = [
                "error" => 0,
                "token" => $token,
                "message" => "Success"
            ];
        } catch (\Exception $e) {
            $resBody = [
                "error" => 1,
                "token" => "",
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
}
