<?php

namespace Digitalriver\DrPay\Controller\Directdebit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use \Magento\Sales\Model\Order;
use \Magento\Quote\Model\QuoteFactory;

class Success extends \Magento\Framework\App\Action\Action
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
    protected $quoteFactory;
    protected $regionModel;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Directory\Model\Region $regionModel,
		\Digitalriver\DrPay\Model\DrConnector $drconnector,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Quote\Api\CartManagementInterface $quoteManagement,
        QuoteFactory $quoteFactory
    ) {
        $this->customerSession = $customerSession;
        $this->order = $order;
        $this->helper =  $helper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->regionModel = $regionModel;
        $this->drconnector = $drconnector;
		$this->jsonHelper = $jsonHelper;
		$this->quoteManagement = $quoteManagement; 
        return parent::__construct($context);
    }
    
    /**
     * Returned From Payment Gateway
     *
     * @return void
     */
    public function execute()
    {
		$quote = $this->checkoutSession->getQuote();
		if($quote && $quote->getId() && $quote->getIsActive()){
			/**
			 * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
			 */
			$resultRedirect = $this->resultRedirectFactory->create();
			if ($this->getRequest()->getParam('sourceId')) {
				$source_id = $this->getRequest()->getParam('sourceId');
				$accessToken = $this->checkoutSession->getDrAccessToken();
				$paymentResult = $this->helper->applyQuotePayment($source_id);				
				$result = $this->helper->createOrderInDr($accessToken);
				if ($result && isset($result["errors"])) {
					$this->messageManager->addError(__('Unable to Place Order!! Payment has been failed'));
					return $resultRedirect->setPath('checkout/cart');
				} else {			
					// "last successful quote"
					$quoteId = $quote->getId();
					$this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
					if(!$quote->getCustomerId()){
						$quote->setCustomerId(null)
							->setCustomerEmail($quote->getBillingAddress()->getEmail())
							->setCustomerIsGuest(true)
							->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
					}
					$quote->collectTotals();
					try{                     					
						// Check quote has any errors
						$isValidQuote = $this->helper->validateQuote($quote);

						if(!empty($isValidQuote)){
							$order = $this->quoteManagement->submit($quote);
							if ($order) {
								$this->checkoutSession->setLastOrderId($order->getId())
										->setLastRealOrderId($order->getIncrementId())
										->setLastOrderStatus($order->getStatus());
							} else{
								$this->helper->cancelDROrder($quote, $result);
								$this->messageManager->addError(__('Unable to Place Order!! Payment has been failed'));
								$this->_redirect('checkout/cart');
								return;						
							}

							$this->_eventManager->dispatch('dr_place_order_success', ['order' => $order, 'quote' => $quote, 'result' => $result, 'cart_result' => '']);
							$this->_redirect('checkout/onepage/success', array('_secure'=>true));
							return;
						} else {
							$this->helper->cancelDROrder($quote, $result);
							$this->_redirect('checkout/cart');
							return;	
						} // end: if
					} catch (\Magento\Framework\Exception\LocalizedException $ex) {
						$this->helper->cancelDROrder($quote, $result);
						$this->_redirect('checkout/cart');
						return;
					} catch (Exception $ex) {
						$this->helper->cancelDROrder($quote, $result);
						$this->_redirect('checkout/cart');
						return;
					} // end: try
				}
			}
		}
        $this->_redirect('checkout/cart');
        return;
    }
}
