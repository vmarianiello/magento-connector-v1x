<?php
/**
 * Digitalriver Helper
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 * @author   Balaji S <balaji.setti@diconium.com>
 */
 
namespace Digitalriver\DrPay\Helper;

use Magento\Framework\App\Helper\Context;

/**
 * Class Data
 */
class Drapi extends \Magento\Framework\App\Helper\AbstractHelper
{
        /**
         * @var session
         */
    protected $session;
        /**
         * @var storeManager
         */
    protected $storeManager;
        /**
         * @var regionModel
         */
    protected $regionModel;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var CartManagementInterface
     */
    private $_cartManagement;

    /**
     * @var Session
     */
    private $_customerSession;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;
    protected $drFactory;
    protected $jsonHelper;
    
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
         * @param Context                                          $context
         * @param \Magento\Checkout\Model\Session                  $session
         * @param \Magento\Store\Model\StoreManagerInterface       $storeManager
         * @param \Magento\Catalog\Api\ProductRepositoryInterface  $productRepository
         * @param \Magento\Quote\Api\CartManagementInterface       $_cartManagement
         * @param \Magento\Customer\Model\Session                  $_customerSession
         * @param \Magento\Checkout\Helper\Data                    $checkoutHelper
         * @param \Magento\Framework\Encryption\EncryptorInterface $enc
         * @param \Magento\Framework\HTTP\Client\Curl              $curl
         * @param \Magento\Directory\Model\Region                  $regionModel
         * @param \Digitalriver\DrPay\Model\DrConnectorFactory $drFactory
         * @param \Magento\Framework\Json\Helper\Data $jsonHelper
         * @param \Digitalriver\DrPay\Logger\Logger                $logger
         * @param \Magento\Framework\App\Response\RedirectInterface $redirect
         */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Api\CartManagementInterface $_cartManagement,
        \Magento\Customer\Model\Session $_customerSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Encryption\EncryptorInterface $enc,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Directory\Model\Region $regionModel,
        \Digitalriver\DrPay\Model\DrConnectorFactory $drFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Digitalriver\DrPay\Logger\Logger $logger,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->_cartManagement = $_cartManagement;
        $this->_customerSession = $_customerSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->regionModel = $regionModel;
        $this->_enc = $enc;
        $this->curl = $curl;
        $this->jsonHelper = $jsonHelper;
        $this->_enc = $enc;
        $this->drFactory = $drFactory;
		$this->remoteAddress = $remoteAddress;
		$this->currencyFactory = $currencyFactory;
        $this->redirect = $redirect;
        parent::__construct($context);
        $this->_logger = $logger;
    }

	/**
     * @return mixed|null
     */
    public function getDrapiPublicKey($storecode = null)
    {
        $drapi_public_key = 'drapi_settings/drapi_config/drapi_public_key';
        return $this->scopeConfig->getValue($drapi_public_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

	/**
     * @return mixed|null
     */
    public function getDrapiSecretKey($storecode = null)
    {
        $drapi_secret_key = 'drapi_settings/drapi_config/drapi_secret_key';
        return $this->scopeConfig->getValue($drapi_secret_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

	/**
     * @return mixed|null
     */
    public function getDrapiUrl($storecode = null)
    {
        $drapi_url = 'drapi_settings/drapi_config/drapi_url';
        return $this->scopeConfig->getValue($drapi_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

	/**
     * @return mixed|null
     */
    public function getLocale($storecode = null)
    {
        $dr_locale = 'dr_settings/config/locale';
        return $this->scopeConfig->getValue($dr_locale, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }
}