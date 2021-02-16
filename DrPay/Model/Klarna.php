<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
namespace Digitalriver\DrPay\Model;

/**
 * Class Klarna
 */
class Klarna extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_KLARNA_CODE = 'drpay_klarna';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_KLARNA_CODE;

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

    /**
     * Get the URL
     *
     * @retun string|null
     */
    public function getJsUrl()
    {
         return trim($this->getConfigData('url'));
    }
    /**
     * Get the KEY
     *
     * @retun string|null
     */
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
