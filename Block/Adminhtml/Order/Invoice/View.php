<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Balancepay\Balancepay\Block\Adminhtml\Order\Invoice;

class View extends \Magento\Sales\Block\Adminhtml\Order\Invoice\View
{
    /**
     * Admin session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_session;

    /**
     * @var null
     */
    protected $_coreRegistry = null;

    /**
     * @var backendSession
     */
    protected $_backendSession;

    /**
     * Constructor
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _construct()
    {
        $this->_objectId = 'invoice_id';
        $this->_controller = 'adminhtml_order_invoice';
        $this->_mode = 'view';
        $this->_session = $this->_backendSession;

        parent::_construct();

        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');

        if (!$this->getInvoice()) {
            return;
        }

        if ($this->_isAllowedAction(
            'Magento_Sales::cancel'
        ) && $this->getInvoice()->canCancel() && !$this->_isPaymentReview()
        ) {
            $this->buttonList->add(
                'cancel',
                [
                    'label' => __('Cancel'),
                    'class' => 'delete',
                    'onclick' => 'setLocation(\'' . $this->getCancelUrl() . '\')'
                ]
            );
        }

        if ($this->_isAllowedAction('Magento_Sales::emails')) {
            $this->addButton(
                'send_notification',
                [
                    'label' => __('Send Email'),
                    'class' => 'send-email',
                    'onclick' => 'confirmSetLocation(\'' . __(
                        'Are you sure you want to send an invoice email to customer?'
                    ) . '\', \'' . $this->getEmailUrl() . '\')'
                ]
            );
        }

        $orderPayment = $this->getInvoice()->getOrder()->getPayment();

        if ($this->_isAllowedAction('Magento_Sales::creditmemo') && $this->getInvoice()->getOrder()->canCreditmemo()) {
            if ($orderPayment->canRefundPartialPerInvoice() &&
                $this->getInvoice()->canRefund() &&
                $orderPayment->getAmountPaid() > $orderPayment->getAmountRefunded() ||
                $orderPayment->canRefund() && !$this->getInvoice()->getIsUsedForRefund()
            ) {
                $this->buttonList->add(
                    'credit-memo',
                    [
                        'label' => __('Online Credit Memo'),
                        'class' => 'credit-memo',
                        'onclick' => 'setLocation(\'' . $this->getCreditMemoUrl() . '\')'
                    ]
                );
            }
        }

        if ($this->_isAllowedAction(
            'Magento_Sales::capture'
        ) && $this->getInvoice()->canCapture() && !$this->_isPaymentReview()
        ) {
            $this->buttonList->add(
                'capture',
                [
                    'label' => __('Capture'),
                    'class' => 'capture',
                    'onclick' => 'setLocation(\'' . $this->getCaptureUrl() . '\')'
                ]
            );
        }

        if ($this->getInvoice()->canVoid()) {
            $this->buttonList->add(
                'void',
                [
                    'label' => __('Void'),
                    'class' => 'void',
                    'onclick' => 'setLocation(\'' . $this->getVoidUrl() . '\')'
                ]
            );
        }

        if ($this->getInvoice()->getId()) {
            $this->buttonList->add(
                'print',
                [
                    'label' => __('Print'),
                    'class' => 'print',
                    'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
                ]
            );
        }
    }
}
