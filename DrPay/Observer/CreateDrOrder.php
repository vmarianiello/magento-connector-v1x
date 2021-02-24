<?php
/**
 * DrPay Observer
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
 
namespace Digitalriver\DrPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 *  CreateDrOrder
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
class CreateDrOrder implements ObserverInterface
{
	/**
	 * @param \Digitalriver\DrPay\Helper\Data            $helper
	 * @param \Magento\Checkout\Model\Session            $session
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 */
    public function __construct(
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Checkout\Model\Session $session,
		\Digitalriver\DrPay\Model\DrConnector $drconnector,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->helper =  $helper;
        $this->session = $session;
        $this->drconnector = $drconnector;
		$this->jsonHelper = $jsonHelper;
        $this->_storeManager = $storeManager;
		$this->_eventManager = $eventManager;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Create order
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer['order'];
        $quote = $observer['quote'];
        if ($quote->getPayment()->getMethod() == \Digitalriver\DrPay\Model\CreditCard::PAYMENT_METHOD_CREDITCARD_CODE || $quote->getPayment()->getMethod() == \Digitalriver\DrPay\Model\ApplePay::PAYMENT_METHOD_APPLE_PAY_CODE) {            
            $accessToken = $this->session->getDrAccessToken();
            $addressId = $quote->getShippingAddress() ? $quote->getShippingAddress()->getCustomerAddressId(): null;
            $billingAddressId = $quote->getBillingAddress() ? $quote->getBillingAddress()->getCustomerAddressId(): null;
            if ($this->session->getDrQuoteError()) {
                if ($addressId && $quote->getShippingAddress()->getSaveInAddressBook()) {
                    $this->addressRepository->deleteById($addressId);
                }
                if ($billingAddressId && $quote->getBillingAddress()->getSaveInAddressBook()) {
                    $this->addressRepository->deleteById($billingAddressId);
                }
                throw new CouldNotSaveException(__('Unable to Place Order'));
            } else {
				$cartresult = '';
                $result = $this->helper->createOrderInDr($accessToken);
                if ($result && isset($result["errors"])) {
                    if ($addressId && $quote->getShippingAddress()->getSaveInAddressBook()) {
                        $this->addressRepository->deleteById($addressId);
                    }
                    if ($billingAddressId && $quote->getBillingAddress()->getSaveInAddressBook()) {
                        $this->addressRepository->deleteById($billingAddressId);
                    }
                    throw new CouldNotSaveException(__('Unable to Place Order'));
                } else {
					$this->_eventManager->dispatch('dr_place_order_success', ['order' => $order, 'quote' => $quote, 'result' => $result, 'cart_result' => $cartresult]);
                }
            }
        }
    }
}
