<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
namespace Digitalriver\DrPay\Controller\Payment;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;

/**
 * Class Success
 */
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
        /**
         * @var \Magento\Quote\Model\QuoteFactory
         */
    protected $quoteFactory;
        /**
         * @var \Magento\Directory\Model\Region
         */
    protected $regionModel;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session       $customerSession
     * \Magento\Sales\Model\Order $order
     * \Magento\Checkout\Model\Session $checkoutSession
     * \Digitalriver\DrPay\Helper\Data $helper
     * \Magento\Directory\Model\Region $regionModel
     * \Magento\Quote\Model\QuoteFactory $quoteFactory
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
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
		\Digitalriver\DrPay\Logger\Logger $logger
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
		$this->_logger = $logger;
        return parent::__construct($context);
    }
    
    /**
     * Klarna Success response
     *
     * @return mixed|null
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
		if($quote && $quote->getId() && $quote->getIsActive()){
			/**
			 * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
			 */			
			$resultRedirect = $this->resultRedirectFactory->create();
			
			$source_id = $this->getRequest()->getParam('sourceId');
			if(empty($source_id)) {
				$source_id = $this->checkoutSession->getDrSourceId();
			}
			
			if(empty($source_id)) {
				$this->messageManager->addError(__("Unable to process"));
				return $resultRedirect->setPath('checkout/cart');
			}

			$result = $this->helper->applyQuotePayment($source_id);             
            if($result && isset($result["errors"])){				
				$this->messageManager->addError(__("Unable to process"));
				return $resultRedirect->setPath('checkout/cart');
			}
			
			$accessToken = $this->checkoutSession->getDrAccessToken();			
			$cartresult = $this->helper->getDrCart();
			$result = $this->helper->createOrderInDr($accessToken);
			if ($result && isset($result["errors"])) {
				$this->messageManager->addError(__("Unable to process"));
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
						$this->_logger->info("DR Cart submitted: " . $result['submitCart']['order']['id']);
						if(isset($result["submitCart"]['paymentMethod']['type']) && $result["submitCart"]['paymentMethod']['type'] == 'payPal') {
							$this->_logger->info("Reset the billing and shipping address to the quote from Paypal");
							// Update Quote's Billing Address details from DR Order creation response
							if (isset($result['submitCart']['billingAddress'])) {
								$billingAddress = $this->helper->getDrAddress('billing', $result);
								if (!empty($billingAddress)) {
									$quote->getBillingAddress()->addData($billingAddress);
								} // end: if
							} // end: if
						}

						$order = $this->quoteManagement->submit($quote);
						if ($order) {
							$this->_logger->info("Submitted Magento Order : " . $order->getId());
							$this->checkoutSession->setLastOrderId($order->getId())
									->setLastRealOrderId($order->getIncrementId())
									->setLastOrderStatus($order->getStatus());
						} else{
							$this->helper->cancelDROrder($quote, $result);
							$this->messageManager->addError(__("Unable to process"));
							$this->_redirect('checkout/cart');
							return;						
						}

						if(isset($result["submitCart"]['paymentMethod']['wireTransfer'])){
							$paymentData = $result["submitCart"]['paymentMethod']['wireTransfer'];
							$order->getPayment()->setAdditionalInformation($paymentData);
						}
						$this->_logger->info("Update Magento Order details with DR tax info");
						$this->_eventManager->dispatch('dr_place_order_success', ['order' => $order, 'quote' => $quote, 'result' => $result, 'cart_result' => $cartresult]);
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
        $this->_redirect('checkout/cart');
        return;
    }
}