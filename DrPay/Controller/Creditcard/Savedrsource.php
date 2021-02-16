<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Controller\Creditcard;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Savedrsource
 */
class Savedrsource extends \Magento\Framework\App\Action\Action
{

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Digitalriver\DrPay\Logger\Logger     $logger
     * @param \Digitalriver\DrPay\Helper\Data       $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Logger\Logger $logger,
        \Digitalriver\DrPay\Helper\Data $helper
    ) {
        $this->helper =  $helper;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        parent::__construct($context);
    }
    /**
     * @return mixed|null
     */
    public function execute()
    {
        $responseContent = [
            'success'        => false,
            'content'        => ''
        ];      
        if($this->getRequest()->getParam('source_id')){
            $source_id = $this->getRequest()->getParam('source_id');
			$this->_checkoutSession->setDrSourceId($source_id);
			$responseContent = [
				'success'        => true,
				'content'        => ''
			]; 
        }
		$response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
