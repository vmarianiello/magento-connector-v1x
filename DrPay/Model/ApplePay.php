<?php

namespace Digitalriver\DrPay\Model;

class ApplePay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_APPLE_PAY_CODE = 'drpay_apple_pay';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_APPLE_PAY_CODE;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    public function getJsUrl()
    {
         return trim($this->getConfigData('url'));
    }

    public function getPublicKey()
    {
         return trim($this->getConfigData('public_key'));
    }
    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
