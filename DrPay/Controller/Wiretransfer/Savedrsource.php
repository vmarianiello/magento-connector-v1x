<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Digitalriver\DrPay\Controller\Wiretransfer;

use Magento\Framework\Controller\ResultFactory;

class Savedrsource extends \Magento\Framework\App\Action\Action
{

	/**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Digitalriver\DrPay\Helper\Data $helper,
		\Digitalriver\DrPay\Logger\Logger $logger
    ) {
		$this->helper =  $helper;
		$this->_checkoutSession = $checkoutSession;
		$this->_logger = $logger;
		parent::__construct($context);
    }

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
