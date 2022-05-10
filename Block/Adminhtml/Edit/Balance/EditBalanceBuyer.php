<?php

namespace Balancepay\Balancepay\Block\Adminhtml\Edit\Balance;

use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;
use Magento\Backend\Block\Template;
use Balancepay\Balancepay\Model\Config;
use Balancepay\Balancepay\Model\BalanceBuyer;
use Magento\Framework\App\Request\Http;

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
     *
     */
    public function __construct(
        Template\Context $context,
        BalanceBuyer $balanceBuyer,
        RequestFactory $requestFactory,
        BalancepayConfig $balancepayConfig,
        Http $request,
        array $data = []
    )
    {
        $this->balanceBuyer = $balanceBuyer;
        $this->requestFactory = $requestFactory;
        $this->balancepayConfig = $balancepayConfig;
        $this->request = $request;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed|string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBuyerId()
    {
        $buyerId = $this->balanceBuyer->getBalanceBuyerId($this->request->getParam('id'));
        $response = $this->getBuyerEmail($buyerId);
        return $response['email'] ?? '';
    }

    /**
     * @param $buyerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isBuyerIdSet()
    {
        return (bool)$this->getBuyerId();
    }
}
