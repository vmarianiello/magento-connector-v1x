<?php
/**
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Controller\Applepay;

use Magento\Framework\Controller\ResultFactory;

class Savedrquote extends \Magento\Framework\App\Action\Action
{
    protected $regionModel;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->helper =  $helper;
        $this->_checkoutSession = $checkoutSession;
		$this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $responseContent = [
            'success'        => false,
            'content'        => __("Unable to process")
        ];

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $isEnabled = $this->helper->getIsEnabled();
        if(!$isEnabled) {
            return $response->setData($responseContent);
        }
        
        $quote = $this->_checkoutSession->getQuote();        
        $drQuoteError = $this->_checkoutSession->getDrQuoteError();
	    if ($drQuoteError === false) {
			$tax_inclusive = $this->scopeConfig->getValue('tax/calculation/price_includes_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $payload = [];
            $itemsArr = [];
            $itemPrice = 0;
            foreach ($quote->getAllVisibleItems() as $item) {
				
				$price = $item->getRowTotal();
				if($tax_inclusive) {
					$price = $item->getRowTotalInclTax();
				}
				if ($item->getDiscountAmount() > 0) {
					$price = $price - $item->getDiscountAmount();
				}
                $itemsArr[] = [
                    'label' => $item->getName(),
                    'amount' => (float)$price,
                ];
            }
            $displayItems = $itemsArr;
            $address = $quote->getBillingAddress();
            if ($address->getId() && $address->getCountryId()) {
                $countryId = $address->getCountryId();
                //Prepare the payload and return in response for DRJS paypal payload
                $payload = [
                    'country' => $countryId,
                    'currency' => $quote->getQuoteCurrencyCode(),
                    'total' => [
                        'label' => "Order Total",
                        'amount' => (float)round($quote->getGrandTotal(), 2)
                    ],
                    'displayItems' => $displayItems,
                    'requestShipping' => false,
                    'requestBilling' => false,
                    'shippingOptions' => [],
                    'style' => [
                        "buttonType" => "plain",
                        "buttonColor" => "light-outline",
                        "buttonLanguage" => "en"
                    ],
                    "waitOnClick" => false
                ];
				 $responseContent = [
                    'success'        => true,
                    'content'        => $payload
                ];
            }
        }        
        $response->setData($responseContent);
        return $response;
    }
}
