<?php
namespace Balancepay\Balancepay\Observer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BalanceAdminhtmlCustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    public function __construct(
        Customer           $customer,
        CustomerFactory    $customerFactory
    )
    {
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
    }

    /**
     * admin customer save after event handler.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getCustomer();
        $customerId = $customer->getId();
        $postData = $observer->getRequest()->getPostValue();
        if (!empty($customerId) && !empty($postData['buyer']['term_options'])) {
            $termOptions = implode(',', $postData['buyer']['term_options']);
            $customer = $this->customer->load($customerId);
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('term_options', $termOptions);
            $customer->updateData($customerData);
            $customerResource = $this->customerFactory->create();
            $customerResource->saveAttribute($customer, 'term_options');
        }
        return $this;
    }
}
