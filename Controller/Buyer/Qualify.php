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

namespace Balancepay\Balancepay\Controller\Buyer;

use Balancepay\Balancepay\Model\BalanceBuyer;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Setup\Exception;

/**
 * Balancepay get checkout token.
 */
class Qualify extends Action
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var resultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var BalancepayConfig
     */
    private $balancepayConfig;

    /**
     * @var BalanceBuyer
     */
    private $balanceBuyer;

    /**
     * Qualify constructor.
     *
     * @param Context $context
     * @param RequestFactory $requestFactory
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param BalancepayConfig $balancepayConfig
     */
    public function __construct(
        Context $context,
        RequestFactory $requestFactory,
        Customer $customer,
        CustomerFactory $customerFactory,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        BalancepayConfig $balancepayConfig,
        BalanceBuyer $balanceBuyer
    ) {
        parent::__construct($context);
        $this->requestFactory = $requestFactory;
        $this->customer = $customer;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->balancepayConfig = $balancepayConfig;
        $this->balanceBuyer = $balanceBuyer;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        if ($this->balancepayConfig->isActive()) {
            $buyerId = $this->customerSession->getCustomer()->getBuyerId() ?? '';
            $email = $this->customerSession->getCustomer()->getEmail();
            if (empty($buyerId) && $email) {
                $buyerId = $this->balanceBuyer->createBuyer(['email' => $email]);
            }
            $qualificationLink = $this->getQualificationLink($buyerId);
            if (!empty($qualificationLink)) {
                $strQualifyLink = str_replace("_", ".", key($qualificationLink)) .
                    '=' . $qualificationLink[key($qualificationLink)];
                return $resultJson->setData(['qualificationLink' => $strQualifyLink]);
            }
        }
        return $resultJson->setData([]);
    }

    /**
     * GetQualificationLink
     *
     * @param string $data
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getQualificationLink($data)
    {
        try {
            if (!empty($data)) {
                return $this->requestFactory
                    ->create(RequestFactory::BUYER_REQUEST_METHOD)
                    ->setRequestMethod('qualification/' . $data)
                    ->setTopic('qualification')
                    ->process();
            }
        } catch (Exception $e) {
            $this->balancepayConfig->log('Qualification Link [Exception: ' .
                $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
        }
        return $data;
    }
}
