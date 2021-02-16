<?php
/**
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Controller\Applepay;

use Magento\Framework\Controller\ResultFactory;

class Saveaddress extends \Magento\Framework\App\Action\Action
{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Digitalriver\DrPay\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Helper\Data $helper
    ) {
        $this->helper =  $helper;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $quote = $this->_checkoutSession->getQuote();
        $cartResult = $this->helper->createFullCartInDr($quote, 1);
        $accessToken = $this->_checkoutSession->getDrAccessToken();
        $responseContent = [];
          // $paymentResult = $this->helper->applyQuotePayment($source_id);
          $payload = [];
          $itemsArr = [];
          $itemPrice = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $itemPrice = $item->getCalculationPrice();
            $itemsArr[] = [
                'label' => $item->getName(),
                'amount' => (float)number_format($itemPrice, 2, ".", ''),
            ];
        }
          $displayItems = $itemsArr;
          $address = $quote->getBillingAddress();
        if ($address->getId() && $address->getCountryId()) {
            $countryId = $address->getCountryId();
            //Prepare the payload and return in response for DRJS paypal payload
            $payload = [
                'shippingOptions' => [],
                'total' => [
                    'label' => "Order Total",
                    'amount' => (float)number_format($quote->getGrandTotal(), 2, ".", '')
                ],
                'displayItems' => $displayItems
            ];
            $responseContent = [
                'status'        => "success",
                'content'        => $payload
            ];
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);
        return $response;
    }
}
