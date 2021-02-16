<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
 
namespace Digitalriver\DrPay\Controller\Creditcard;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Getcards
 */
class Getcards extends \Magento\Framework\App\Action\Action
{

    /**
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Checkout\Model\Session        $checkoutSession
     * @param \Digitalriver\DigitalRiver\Helper\Data $helper
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
    /**
     * @return mixed|null
     */
    public function execute()
    {
        $responseContent = [
            'success'        => false
        ];
        $cardResult = $this->helper->getSavedCards();
        if ($cardResult) {
            $responseContent = [
                'success'        => true,
                'content'        => $cardResult
            ];
        }
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
