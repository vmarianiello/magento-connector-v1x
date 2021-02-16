<?php

namespace Digitalriver\DrPay\Model;

class DirectDebit extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_DIRECT_DEBIT_CODE = 'drpay_direct_debit';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_DIRECT_DEBIT_CODE;

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
