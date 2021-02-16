<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Block\Privacy;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Cms\Model\BlockFactory;

/**
 * Class Terms
 */
class Terms extends Template
{

    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context   $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Cms\Model\BlockFactory                    $cmsBlock
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        BlockFactory $cmsBlock
    ) {
        $this->cmsBlock = $cmsBlock;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     *
     * @return type
     */
    public function getPrivacyBlock()
    {
        return $this->cmsBlock->create()->load($this->getTermsBlockId());
    }

    /**
     *
     * @return type
     */
    public function getTermsBlockId()
    {
        $key_term = 'dr_settings/config/terms_cms_block';
        return $this->scopeConfig->getValue($key_term, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
