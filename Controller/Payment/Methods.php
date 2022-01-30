<?php
namespace Balancepay\Balancepay\Controller\Payment;

use Magento\Framework\App\Action\Action;

/**
 * Class Methods
 * @package Balancepay\Balancepay\Controller\Payment
 */
class Methods extends Action
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}

?>
