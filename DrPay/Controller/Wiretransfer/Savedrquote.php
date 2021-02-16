<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Controller\Wiretransfer;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Savedrquote
 */
class Savedrquote extends \Magento\Framework\App\Action\Action
{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Digitalriver\DrPay\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Digitalriver\DrPay\Helper\Data $helper
    ) {
        $this->helper =  $helper;
        parent::__construct($context);
    }
	/**
     * @return mixed|null
     */
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
		$responseContent = $this->helper->setSourcePayload('wireTransfer');
		if($responseContent['success'] === true) {
			$responseContent['content']['payload']['wireTransfer'] = (object) null;
		}
        $response->setData($responseContent);
        return $response;
    }
}
