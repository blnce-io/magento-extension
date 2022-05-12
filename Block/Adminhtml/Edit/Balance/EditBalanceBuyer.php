<?php

namespace Balancepay\Balancepay\Block\Adminhtml\Edit\Balance;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Backend\Block\Template;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\BalanceBuyer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;

class EditBalanceBuyer extends Template
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'balance/edit_balance_buyer.phtml';

    /**
     * @var BalanceBuyer
     */
    private $balanceBuyer;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var Config
     */
    private $balancepayConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Template\Context $context
     * @param BalanceBuyer $balanceBuyer
     * @param RequestFactory $requestFactory
     * @param Config $balancepayConfig
     * @param Http $request
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        BalanceBuyer $balanceBuyer,
        RequestFactory $requestFactory,
        BalancepayConfig $balancepayConfig,
        Http $request,
        array $data = []
    ) {
        $this->balanceBuyer = $balanceBuyer;
        $this->requestFactory = $requestFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->request = $request;
        parent::__construct($context, $data);
    }

    /**
     * GetBuyerId
     *
     * @return mixed|string
     * @throws NoSuchEntityException
     */
    public function getBuyerId()
    {
        $buyerId = $this->balanceBuyer->getBalanceBuyerId($this->request->getParam('id'));
        $response = $this->getBuyerEmail($buyerId);
        return $response['email'] ?? '';
    }

    /**
     * GetBuyerEmail
     *
     * @param mixed $buyerId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getBuyerEmail($buyerId)
    {
        $response = [];
        try {
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
     * IsBuyerIdSet
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isBuyerIdSet()
    {
        return (bool)$this->getBuyerId();
    }
}
