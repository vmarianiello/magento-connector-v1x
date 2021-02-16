<?php

namespace Digitalriver\DrPay\Model\DirectDebit;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ConfigProvider implements ConfigProviderInterface
{

    const PAYMENT_METHOD_DIRECT_DEBIT_CODE = 'drpay_direct_debit';
    /**
     * @var string[]
     */
    protected $_methodCode = self::PAYMENT_METHOD_DIRECT_DEBIT_CODE;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * $_method.
     *
     * @var Magento\Payment\Helper\Data
     */
    protected $_method;
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * __construct constructor.
     *
     * @param PaymentHelper                              $paymentHelper
     * @param ScopeConfigInterface                       $scopeConfig
     * @param Session $checkoutSession
     * @param Escaper                                    $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        ScopeConfigInterface $_scopeConfig,
        CheckoutSession $checkoutSession,
        Escaper $escaper
    ) {
        $this->_method = $paymentHelper->getMethodInstance($this->_methodCode);
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->_scopeConfig = $_scopeConfig;
    }

    /**
     * getConfig function to return cofig data to payment renderer.
     *
     * @return []
     */
    public function getConfig()
    {
        // var_dump($this->checkoutSession->getQuote()->getStore()->getCode());die;
        $config = [];
        $currency_check = true;
        $country_check = true;
        $allowed_country_arr = "";
        $allowed_currency_path = 'payment/drpay_direct_debit/allow_currency';
        $allowed_currency = $this->_scopeConfig->getValue($allowed_currency_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->checkoutSession->getQuote()->getStore());
        $current_currency = $this->checkoutSession->getQuote()->getStore()->getCurrentCurrency()->getCode();
        $allowed_currency_arr = (isset($allowed_currency))?explode(",", $allowed_currency):'';
        
        $current_country = $this->checkoutSession->getQuote()->getBillingAddress()->getCountryId();
        $allow_specific_country_path = 'payment/drpay_direct_debit/allowspecific';
        $allow_specific_country = $this->_scopeConfig->getValue($allow_specific_country_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->checkoutSession->getQuote()->getStore());
        if ($allow_specific_country == 1) {
            $allowed_country_path = 'payment/drpay_direct_debit/specificcountry';
            $allowed_country = $this->_scopeConfig->getValue($allowed_country_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->checkoutSession->getQuote()->getStore());
            $allowed_country_arr = (isset($allowed_country))?explode(",", $allowed_country):'';
        }
        if ((is_array($allowed_currency_arr) && !in_array($current_currency, $allowed_currency_arr))) {
            $currency_check = false;
        }
        if ((is_array($allowed_country_arr) && !in_array($current_country, $allowed_country_arr))) {
            $country_check = false;
        }
        if ($this->_method->isAvailable() && ($currency_check && $country_check)) {
            $isAvail = true;
        } else {
            $isAvail = false;
        }

        $config = [
            'payment' => [
                'drpay_direct_debit' => [
                    'js_url' => $this->_method->getJsUrl(),
                    'public_key' => $this->_method->getPublicKey(),
                    'is_active' => $isAvail,
                    'title' => $this->_method->getTitle(),
                ],
            ],
        ];
        if ($isAvail) {
            $config['payment']['instructions'][$this->_methodCode] = $this->getInstructions($this->_methodCode);
        }
        return $config;
    }
    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->_method->getInstructions()));
    }
}
