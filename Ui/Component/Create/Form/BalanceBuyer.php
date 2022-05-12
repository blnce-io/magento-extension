<?php

namespace Balancepay\Balancepay\Ui\Component\Create\Form;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Balancepay\Balancepay\Model\Request\Factory as RequestFactory;

class BalanceBuyer implements OptionSourceInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;
    /**
     * @var Http
     */
    private $request;

    /**
     * @var \Balancepay\Balancepay\Model\BalanceBuyer
     */
    private $balanceBuyer;

    /**
     * BalanceBuyer constructor.
     *
     * @param RequestFactory $requestFactory
     * @param MessageManagerInterface $messageManager
     * @param \Balancepay\Balancepay\Model\BalanceBuyer $balanceBuyer
     * @param Http $request
     */
    public function __construct(
        RequestFactory $requestFactory,
        MessageManagerInterface $messageManager,
        \Balancepay\Balancepay\Model\BalanceBuyer $balanceBuyer,
        Http $request
    ) {
        $this->balanceBuyer = $balanceBuyer;
        $this->requestFactory = $requestFactory;
        $this->messageManager = $messageManager;
        $this->request = $request;
    }

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $response = $this->requestFactory
                ->create(RequestFactory::BUYER_REQUEST_METHOD)
                ->setRequestMethod('buyers')
                ->setTopic('getbuyers')
                ->process();
            $buyerId = $this->balanceBuyer->getBalanceBuyerId($this->request->getParam('id'));
            foreach ($response as $value) {
                if ($value['id'] != $buyerId) {
                    $options[] = ['label' => $value['email'], 'value' => $value['id']];
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $options;
    }
}
