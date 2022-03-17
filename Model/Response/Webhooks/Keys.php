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

namespace Balancepay\Balancepay\Model\Response\Webhooks;

use Balancepay\Balancepay\Model\AbstractResponse;
use Magento\Store\Model\ScopeInterface;

class Keys extends AbstractResponse
{
    /**
     * @var string
     */
    protected $_webhookSecret;

    /**
     * Process
     *
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
     * Update
     *
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
     * GetRequiredResponseDataKeys
     *
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return ['webhookSecret'];
    }

    /**
     * GetWebhookSecret
     *
     * @return string
     */
    public function getWebhookSecret()
    {
        return $this->_webhookSecret;
    }
}
