<?php
namespace Balancepay\Balancepay\ViewModel;

use Balancepay\Balancepay\Helper\Data as BalancepayHelper;
use Balancepay\Balancepay\Model\Config as BalancepayConfig;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;

class CardList implements ArgumentInterface
{
    /**
     * @var BalancepayHelper
     */
    protected $balancepayHelper;

    /**
     * @var BalancepayConfig
     */
    protected $balancepayConfig;

    /**
     * @var AbstractBlock
     */
    protected $abstractBlock;

    /**
     * CardList constructor.
     *
     * @param Template $template
     * @param BalancepayHelper $balancepayHelper
     * @param BalancepayConfig $balancepayConfig
     */
    public function __construct(
        Template $template,
        BalancepayHelper $balancepayHelper,
        BalancepayConfig $balancepayConfig
    ) {
        $this->template = $template;
        $this->balancepayHelper = $balancepayHelper;
        $this->balancepayConfig = $balancepayConfig;
    }

    /**
     * GetCustomerSessionId
     *
     * @return mixed
     */
    public function getCustomerSessionId()
    {
        return $this->balancepayHelper->getCustomerSessionId();
    }

    /**
     * GetBuyerDetails
     *
     * @param $customerId
     * @return array
     */
    public function getBuyerDetails()
    {
        $response = [];
        try {
            $response = $this->balancepayHelper->getBuyerAmount();
        } catch (\Exception $e) {
            $this->balancepayConfig->log('Webhook\Checkout\Charged::execute() [Exception: ' .
                $e->getMessage() . "]\n" . $e->getTraceAsString(), 'error');
        }
        return $response;
    }

    /**
     * FormattedAmount
     *
     * @param int|string $price
     * @return float|string
     */
    public function formattedAmount($price)
    {
        return $this->balancepayHelper->formattedAmount($price);
    }

    /**
     * GetCcIconUrl
     *
     * @param string $type
     * @return false|string
     */
    public function getCcIconUrl($type = '')
    {
        if (!empty($this->balancepayHelper->ccIcons[$type])) {
            $imageName = $this->balancepayHelper->ccIcons[$type];
            return $this->template->getViewFileUrl('Magento_Payment::images/cc/' . $imageName . '.png');
        }
        return false;
    }
}
