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

namespace Balancepay\Balancepay\Model\Response;

use Balancepay\Balancepay\Model\AbstractResponse;
use Magento\Store\Model\ScopeInterface;

/**
 * Balancepay webhooks/keys response model.
 */
class WebhooksKeys extends AbstractResponse
{
    /**
     * @var string
     */
    protected $_webhookSecret;

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        parent::process();

        $body = $this->getBody();
        $this->_webhookSecret = $body['webhookSecret'];

        return $this;
    }

    /**
     * @method save
     * @param  string                $scope Scope
     * @param  int|null              $storeId
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update($scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        $this->_balancepayConfig->updateWebhookSecret($this->getWebhookSecret(), $scope, $storeId);
        return $this;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return ['webhookSecret'];
    }

    /**
     * @return string
     */
    public function getWebhookSecret()
    {
        return $this->_webhookSecret;
    }
}
