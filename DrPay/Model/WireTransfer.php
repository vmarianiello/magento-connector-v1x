<?php

namespace Digitalriver\DrPay\Model;

/**
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class WireTransfer extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_WIRE_TRANSFER_CODE = 'drpay_wire_transfer';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_WIRE_TRANSFER_CODE;

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