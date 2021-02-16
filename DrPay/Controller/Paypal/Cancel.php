<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
namespace Digitalriver\DrPay\Controller\Paypal;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Cancel
 */
class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session       $customerSession
     * @param \Magento\Sales\Model\Order            $order
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Digitalriver\DrPay\Helper\Data       $helper
     */

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Helper\Data $helper
    ) {
        $this->customerSession = $customerSession;
        $this->order = $order;
        $this->helper =  $helper;
        $this->checkoutSession = $checkoutSession;
        return parent::__construct($context);
    }
    
    /**
     * Canceled paypal transaction
     *
     * @return mixed|null
     */
    public function execute()
    {
         try {
			$this->messageManager->addSuccessMessage(
				__('Paypal Checkout has been canceled.')
			);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Unable to cancel Paypal Checkout'));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/cart');
    }
}
