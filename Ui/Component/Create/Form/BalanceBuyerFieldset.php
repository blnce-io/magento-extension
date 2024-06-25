<?php
namespace Balancepay\Balancepay\Ui\Component\Create\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\ComponentVisibilityInterface;
use \Magento\Ui\Component\Form\Fieldset;

class BalanceBuyerFieldset extends Fieldset implements ComponentVisibilityInterface
{
    private $context;
    
    /**
     * @param ContextInterface $context
     * @param array $components
     * @param array $data
     */

    public function __construct(
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        $this->context = $context;
        parent::__construct($context, $components, $data);
    }

    /**
     * IsComponentVisible
     *
     * @return bool
     */
    public function isComponentVisible(): bool
    {
        $customerId = $this->context->getRequestParam('id');
        if ($customerId) {
            return true;
        }
        return false;
    }
}
