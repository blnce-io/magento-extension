<?php
namespace Balancepay\Balancepay\Plugin;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;

class CustomerSessionContext
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @param Session $customerSession
     * @param Context $httpContext
     */
    public function __construct(
        Session $customerSession,
        Context $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * AroundDispatch
     *
     * @param ActionInterface $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(
        ActionInterface $subject,
        \Closure $proceed,
        RequestInterface $request
    ) {
        $this->httpContext->setValue(
            'customer_id',
            $this->customerSession->getCustomerId(),
            false
        );

        $this->httpContext->setValue(
            'customer_name',
            $this->customerSession->getCustomer()->getName(),
            false
        );

        $this->httpContext->setValue(
            'customer_email',
            $this->customerSession->getCustomer()->getEmail(),
            false
        );

        return $proceed($request);
    }
}
