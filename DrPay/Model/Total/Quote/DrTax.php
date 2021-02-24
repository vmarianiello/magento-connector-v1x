<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
namespace Digitalriver\DrPay\Model\Total\Quote;

use Magento\Framework\App\Area;

class DrTax extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Digitalriver\DrPay\Helper\Data $helper
    ) {
        $this->setCode('dr_tax');
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
		$this->currencyFactory = $currencyFactory;
		$this->scopeConfig = $scopeConfig;
                $this->helper =  $helper;
    }
    
    /**
     * Collect totals process.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $address = $shippingAssignment->getShipping()->getAddress();
        $billingaddress = $quote->getBillingAddress();
        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }	
		
		$accessToken = $this->_checkoutSession->getDrAccessToken();
		$validateCall = $this->helper->validateCartCall();

		if(!empty($accessToken) && $validateCall === true){
			$tax_inclusive = $this->scopeConfig->getValue('tax/calculation/price_includes_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

			$drtax = $this->_checkoutSession->getDrTax();
			
			$productTotal = $this->_checkoutSession->getDrProductTotal();			
			$productTotalExcl = $this->_checkoutSession->getDrProductTotalExcl();
			
			$orderTotal = $this->_checkoutSession->getDrOrderTotal();
			
			$shippingAndHandlingExcl = $this->_checkoutSession->getDrShippingAndHandlingExcl();

			if($tax_inclusive) {
				$total->setShippingAmount($shippingAndHandlingExcl);
			}
			$total->setSubtotalInclTax($productTotal);
			$total->setSubtotal($productTotalExcl);
			
			$total->setBaseGrandTotal($this->convertToBaseCurrency($orderTotal));
			$total->setGrandTotal($orderTotal);			
			
			$total->setBaseTaxAmount($this->convertToBaseCurrency($drtax));
			$total->setTaxAmount($drtax);
		}
        return $this;
    }
    /**
     * Fetch (Retrieve data as array)
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     * @internal param \Magento\Quote\Model\Quote\Address $address
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $quote->getDrTax();
        if ($amount == 0) {
            $billingaddress = $quote->getBillingAddress();
            $amount = $billingaddress->getTaxAmount();
        }
        $result = [
            'code' => $this->getCode(),
            'title' => __('Tax'),
            'value' => $amount
        ];
        
        return $result;
    }

	public function convertToBaseCurrency($price){
        $currentCurrency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $baseCurrency = $this->_storeManager->getStore()->getBaseCurrency()->getCode();
        $rate = $this->currencyFactory->create()->load($currentCurrency)->getAnyRate($baseCurrency);
        $returnValue = $price * $rate;
        return $returnValue;
    }
}
