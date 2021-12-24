<?php
namespace Balancepay\Balancepay\Ui\Component\Create\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\ComponentVisibilityInterface;
use \Magento\Ui\Component\Form\Fieldset;
use Webkul\Marketplace\Block\Adminhtml\Customer\Edit;

/**
 * Balance Vendor fieldset class
 */
class BalanceVendorFieldset extends Fieldset implements ComponentVisibilityInterface
{
    /**
     * @param ContextInterface $context
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        Edit $customerEdit,
        array $components = [],
        array $data = []
    ) {
        $this->context = $context;
        $this->customerEdit = $customerEdit;
        parent::__construct($context, $components, $data);
    }

    /**
     * @return bool
     */
    public function isComponentVisible(): bool
    {
        $customerId = $this->context->getRequestParam('id');
        $collection = $this->customerEdit->getMarketplaceUserCollection();
        if ($customerId && count($collection)>0) {
            return true;
        }
        return false;
    }
}
