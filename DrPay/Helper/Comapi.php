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
class Comapi extends \Magento\Framework\App\Helper\AbstractHelper
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
         * @param \Magento\Customer\Model\Session                  $_customerSession
         * @param \Magento\Checkout\Helper\Data                    $checkoutHelper
         * @param \Magento\Framework\Encryption\EncryptorInterface $enc
         * @param \Magento\Framework\HTTP\Client\Curl              $curl
         * @param \Digitalriver\DrPay\Model\DrConnectorFactory $drFactory
         * @param \Magento\Framework\Json\Helper\Data $jsonHelper
         * @param \Digitalriver\DrPay\Logger\Logger                $logger
         * @param \Magento\Framework\App\Response\RedirectInterface $redirect
         */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $_customerSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Encryption\EncryptorInterface $enc,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Digitalriver\DrPay\Model\DrConnectorFactory $drFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
		\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Digitalriver\DrPay\Logger\Logger $logger,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->_customerSession = $_customerSession;
        $this->checkoutHelper = $checkoutHelper;
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
     * @return string|null
     */
    public function convertTokenToFullAccessToken($quote)
    {
        $address = $quote->getBillingAddress();
        if ($this->_customerSession->isLoggedIn()) {
            $external_reference_id = $address->getEmail().$address->getCustomerId();
        } else {
            $guestEmail = $this->session->getGuestCustomerEmail();
            $external_reference_id = $guestEmail.$quote->getId();
        }        
        try {
            $this->createShopperInDr($quote, $external_reference_id);
            if ($external_reference_id) {
                $url = $this->getDrBaseUrl()."oauth20/token?format=json";
                $data = [
                   "grant_type" => "client_credentials",
                   "dr_external_reference_id" => $external_reference_id,
                   "format" => "json"
                ];
				$this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
				$this->curl->setCredentials($this->getDrAuthUsername(), $this->getDrAuthPassword());
				$this->curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
				$this->curl->post($url, $data);

				$result = $this->curl->getBody();
				$result = json_decode($result, true);
				if (isset($result["access_token"])) {
					$this->session->setDrAccessToken($result["access_token"]);
					return $result["access_token"];
				}
            }
        } catch (Exception $e) {
            $this->_logger->error("Error in Token request: ".$e->getMessage());
        }
		return '';
    }

	public function checkDrAccessTokenValidation($token){
		try{
			if($token){			
				$url = $this->getDrBaseUrl()."oauth20/access-tokens?format=json&token=".$token;
				
				$this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
				$this->curl->addHeader('Content-Type', 'application/json');
				$this->curl->get($url);
				$result = $this->curl->getBody();

				$result = json_decode($result, true);
				$currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
				if($result["currency"] != $currency){
					$this->updateAccessTokenCurrency($token, $currency);
				}
				if(isset($result["expiresIn"]) && $result["expiresIn"] > 1000){
					return true;
				}
			}
		} catch (Exception $e) {
            $this->_logger->error("Error in Token validation: ".$e->getMessage());
        }
		return false;
	}

	/**
     * @return null
     */
    public function createShopperInDr($quote, $external_reference_id)
    {
        try{
			if ($external_reference_id) {			
				$address = $quote->getBillingAddress();
				$firstname = $address->getFirstname();
				$lastname = $address->getLastname();
				if ($this->_customerSession->isLoggedIn()) {
					$email = $address->getEmail();
				} else {
					$email = $this->session->getGuestCustomerEmail();
				}
				
				$username = $external_reference_id;
				$currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
				$apikey = $this->getDrApiKey();
				$locale = $this->getLocale();				
				if ($firstname) {					
					$url = $this->getDrBaseUrl()."v1/shoppers?format=json&apiKey=".$apikey;
					$shopper['firstName'] = $firstname;
					$shopper['lastName'] = $firstname;
					$shopper['externalReferenceId'] = $username;
					$shopper['username'] = $username;
					$shopper['emailAddress'] = $email;
					$shopper['locale'] = $locale;
					$shopper['currency'] = $currency;

					$data['shopper'] = $shopper;
					$this->_logger->info(json_encode($data));
					$this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
					$this->curl->addHeader('Content-Type', 'application/json');
					$this->curl->post($url, json_encode($data));
					$result = $this->curl->getBody();
					$this->_logger->info(json_encode($result));
				}
			}
		} catch (Exception $e) {
            $this->_logger->error("Error in create shopper: ".$e->getMessage());
        }
        return;
    }
	/**
     * @param  mixed $accessToken, $currentCurrency
     * @return null
     */
    public function updateAccessTokenCurrency($accessToken, $currentCurrency)
    {
        if ($accessToken) {
            $apikey = $this->getDrApiKey();
            $locale = $this->getLocale();
            $drBaseUrl = $this->getDrBaseUrl();
            if ($apikey && $locale && $drBaseUrl) {
                $data = [];
                $url = $this->getDrBaseUrl()."v1/shoppers/me?locale=".$locale."&currency=".$currentCurrency."&format=json";
                $this->_logger->info("Url: ".$url);
                $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->curl->addHeader("Authorization", "Bearer ".$accessToken);
                $this->curl->post($url, $data);
                $result = $this->curl->getBody();
            }
        }
        return;
    }

	/**
     * @param  mixed $accessToken
     * @return mixed|null
     */
    public function deleteDrCartItems($accessToken)
    {
		// Delete method can only be done via \Zend\Http\Request()
		$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/line-items?format=json";
		$request = new \Zend\Http\Request();
		$httpHeaders = new \Zend\Http\Headers();
		$client = new \Zend\Http\Client();
		$httpHeaders->addHeaders(
			[
			'Authorization' => 'Bearer ' . $accessToken,
			'Content-Type' => 'application/json'
			]
		);
		$request->setHeaders($httpHeaders);
		$request->setMethod(\Zend\Http\Request::METHOD_DELETE);
		$request->setUri($url);
		$response = $client->send($request);
        
        return;
    }


	public function createCart($data, $quote)
	{
		$response = false;
        if ($this->session->getDrAccessToken()) {			
            $accessToken = $this->session->getDrAccessToken();			
			if ($accessToken) {
				$checktoken = $this->checkDrAccessTokenValidation($accessToken);
				if(!$checktoken){
					$accessToken = $this->convertTokenToFullAccessToken($quote);
					$this->session->setDrAccessToken($accessToken);
				}
			}
        } else {
            $accessToken = $this->convertTokenToFullAccessToken($quote);
			$this->checkDrAccessTokenValidation($accessToken);
            $this->session->setDrAccessToken($accessToken);
        }
        $token = '';
		$this->_logger->info("TOKEN: ".$accessToken);
        if ($accessToken && $this->getDrBaseUrl()) {
			try{
				$testorder = $this->getIsTestOrder();
				if ($testorder) {
					$url = $this->getDrBaseUrl() .
					"v1/shoppers/me/carts/active?format=json&skipOfferArbitration=true&testOrder=true&expand=lineItems.lineItem.customAttributes";
				} else {
					$url = $this->getDrBaseUrl() .
					"v1/shoppers/me/carts/active?format=json&skipOfferArbitration=true&expand=lineItems.lineItem.customAttributes";
				}
				if(isset($data['cart']['appliedOrderOffers']) && isset($data['cart']['appliedOrderOffers']['shippingOffer'])) {
					$data['cart']['appliedOrderOffers']["shippingOffer"]["offerId"] = $this->getShippingOfferId();
				}			
				
				$original_data = $data;	
				$data = $this->encryptRequest(json_encode($data));
				$checksum = sha1(base64_encode($data));					
				$existingChecksum = $this->session->getSessionCheckSum();
				if(!empty($existingChecksum) && $checksum == $existingChecksum){					
					$drresult = $this->session->getDrResult();					
					if($drresult){
						$result = json_decode(base64_decode($drresult), true);
						return $result;
					}
				}

				$this->_logger->info("\n\nREQUEST: ".json_encode($original_data));
				$this->session->setSessionCheckSum($checksum);					
				$this->deleteDrCartItems($accessToken);                    
				$this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
				$this->curl->addHeader("Content-Type", "application/json");
				$this->curl->addHeader("Authorization", "Bearer ".$accessToken);
				$this->curl->post($url, $data);
				$result = $this->curl->getBody();
				$result = json_decode($result, true);
				
				
				
				if (isset($result["errors"])) {
					$this->_logger->error("\n\nERRORS : ".json_encode($result));
					return $result;
				}

				// add logic to extract the data from the result in a readable format
				$response = array();

				$response['id'] = $result['cart']['id'];
				
				$shippingTax = 0;
				$productTax = 0;

				if(isset($result["cart"]['lineItems']) && isset($result["cart"]['lineItems']['lineItem'])) {					
					$lineItems = $result["cart"]['lineItems']['lineItem'];
					foreach($lineItems as $item){
						$productTax += $item['pricing']['productTax']['value'];
						$shippingTax += $item['pricing']['shippingTax']['value'];						
					}
				}

				$response['productTax'] = $productTax;
				$response['shippingTax'] = $shippingTax;

				$response['shippingAndHandling'] = 0;
				if(isset($result["cart"]['pricing']['shippingAndHandling'])) {
					$response['shippingAndHandling'] = $result["cart"]['pricing']['shippingAndHandling']['value'];
				}

				$response['orderTotal'] = $result["cart"]['pricing']['orderTotal']['value'];
				$response['orderTax'] = $result["cart"]["pricing"]["tax"]["value"];

				
				

				$this->session->setDrResult(base64_encode(json_encode($response)));

			} catch (Exception $e) {
				$this->_logger->error("Error in cart creation.".$e->getMessage());
			}
		}        
		return $response;
	}

	/**
     * @param  mixed $data
     * @return mixed|null
     */
    public function encryptRequest($data)
    {
        $key = $this->getEncryptionKey();
        $method = 'AES-128-CBC';
        $encrypt = trim(openssl_encrypt($data, $method, $key, 0, $key));
        return $encrypt;
    }

	/**
     * @param  mixed $sourceId
     * @return mixed|null
     */
    public function applyQuotePayment($sourceId = null)
    {
		$result = "";
		if ($this->getDrBaseUrl() && $this->session->getDrAccessToken() && $sourceId!=null) {
			$accessToken = $this->session->getDrAccessToken();
			$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/apply-payment-method?format=json";
			$data["paymentMethod"]["sourceId"] = $sourceId;
			$this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
			$this->curl->addHeader("Content-Type", "application/json");
			$this->curl->addHeader("Authorization", "Bearer " . $accessToken);
			$this->curl->post($url, json_encode($data));
			$result = $this->curl->getBody();
			$result = json_decode($result, true);
			$this->_logger->info("Apply Quote Result :".json_encode($result));

			if (isset($result['errors']) && count($result['errors']['error'])>0) {
				$result = "";
			}
		}
        return $result;
    }

	/**
     * @param  mixed $paymentId
     * @return mixed|null
     */
    public function applyQuotePaymentOptionId($paymentId = null)
    {
        $result = "";
        $data = [];
        if ($this->getDrBaseUrl() && $this->session->getDrAccessToken() && $paymentId!=null) {
            $accessToken = $this->session->getDrAccessToken();
            $url = $this->getDrBaseUrl().
            "v1/shoppers/me/carts/active/apply-shopper?paymentOptionId=".$paymentId."&format=json";
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->post($url, $data);
            $result = $this->curl->getBody();
            $result = json_decode($result, true);
            $this->_logger->info("Apply Quote Result :".json_encode($result));
            if (isset($result['errors']) && count($result['errors']['error'])>0) {
                $result = "";
            }
        }
        return $result;
    }


	/**
     * @param  mixed  $sourceId
     * @param  string $name
     * @return mixed|null
     */
    public function applySourceShopper($sourceId = null, $name = "Default Card")
    {
        if ($this->getDrBaseUrl() && $this->session->getDrAccessToken() && $sourceId!=null) {
            $accessToken = $this->session->getDrAccessToken();
            $url = $this->getDrBaseUrl()."v1/shoppers/me/payment-options?format=json";
            $data["paymentOption"]["nickName"] = $name;
            $data["paymentOption"]["isDefault"] = "true";
            $data["paymentOption"]["sourceId"] = $sourceId;
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->post($url, json_encode($data));
            $result = $this->curl->getBody();
        }
    }
    /**
     * @return array|null
     */
    public function getSavedCards()
    {
        $result = "";
        if ($this->getDrBaseUrl() && $this->session->getDrAccessToken()) {
            $accessToken = $this->session->getDrAccessToken();
            $url = $this->getDrBaseUrl()."v1/shoppers/me/payment-options?expand=all&format=json";
            
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->get($url);
            $result = $this->curl->getBody();
            $result = json_decode($result, true);
        }
        return $result;
    }

	/**
     * @param  mixed $accessToken
     * @return mixed|null
     */
    public function applyShopperToCart($accessToken)
    {
        if ($this->getDrBaseUrl() && $accessToken) {
            $url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/apply-shopper?format=json";
            $data = [];
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->post($url, $data);
            $result = $this->curl->getBody();
            $result = json_decode($result, true);
            return $result;
        }
        return;
    }

	/**
     * @param  mixed $accessToken
     * @return mixed|null
     */
    public function createOrderInDr($accessToken)
    {
        if ($this->getDrBaseUrl() && $accessToken) {
			$ip = $this->remoteAddress->getRemoteAddress();
            $url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/submit-cart?expand=lineItems.lineItem.customAttributes&format=json&ipAddress=".$ip;
            $data = [];
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_TIMEOUT, 60);
            $this->curl->addHeader("Authorization", "Bearer " . $accessToken);
            $this->curl->post($url, $data);
            $result = $this->curl->getBody();
            $result = json_decode($result, true);
            $this->_logger->info("createOrderInDr Result :".json_encode($result));
            return $result;
        }
        return;
    }

	/**
     *
     * @return type
     */
    public function getDrPostUrl($storecode = null)
    {
        return $this->scopeConfig->getValue('dr_settings/config/dr_post_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

    /**
     *
     * @return type
     */
    public function getDrRefundUrl($storecode = null)
    {
        return $this->scopeConfig->getValue('dr_settings/config/dr_refund_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }	

	/**
     *
     * @return type
     */
    public function getCompanyId($storecode = null)
    {
        return $this->scopeConfig->getValue('dr_settings/config/company_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

    public function getDrRefundUsername($storecode = null)
    {
        return $this->scopeConfig->getValue('dr_settings/config/dr_refund_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

    public function getDrRefundPassword($storecode = null)
    {
        $dr_refund_pass = $this->scopeConfig->getValue('dr_settings/config/dr_refund_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
        return $this->_enc->decrypt($dr_refund_pass);
    }

    public function getDrRefundAuthUsername($storecode = null)
    {
        return $this->scopeConfig->getValue('dr_settings/config/dr_refund_auth_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

    public function getDrRefundAuthPassword($storecode = null)
    {
        $dr_auth_pass = $this->scopeConfig->getValue('dr_settings/config/dr_refund_auth_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
        return $this->_enc->decrypt($dr_auth_pass);
    }	
	/**
     * @return mixed|null
     */
    public function getDrStoreUrl($storecode = null)
    {
        $key_token_url = 'dr_settings/config/session_token_url';
        return $this->scopeConfig->getValue($key_token_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }
    /**
     * @return mixed|null
     */
    public function getDrBaseUrl($storecode = null)
    {
        $url_key = 'dr_settings/config/dr_url';
        return $this->scopeConfig->getValue($url_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

	/**
     * @return mixed|null
     */
    public function getDrApiKey($storecode = null)
    {
        $dr_key_api = $this->scopeConfig->getValue('dr_settings/config/dr_api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
        return $this->_enc->decrypt($dr_key_api);
    }
    /**
     * @return mixed|null
     */
    public function getDrAuthUsername($storecode = null)
    {
        $dr_auth_name = 'dr_settings/config/dr_auth_username';
        return $this->scopeConfig->getValue($dr_auth_name, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }
    /**
     * @return mixed|null
     */
    public function getDrAuthPassword($storecode = null)
    {
        $dr_auth_pass = $this->scopeConfig->getValue('dr_settings/config/dr_auth_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
        return $this->_enc->decrypt($dr_auth_pass);
    }

    /**
     * @return mixed|null
     */
    public function getIsTestOrder($storecode = null)
    {
        $dr_test_key = 'dr_settings/config/testorder';
        return $this->scopeConfig->getValue($dr_test_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }
    /**
     * @return mixed|null
     */
    public function getEncryptionKey($storecode = null)
    {
        $dr_encrypt_key = 'dr_settings/config/encryption_key';
        return $this->scopeConfig->getValue($dr_encrypt_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
    }

	/**
     * @return mixed|null
     */
    public function getShippingOfferId($storecode = null)
    {
        $dr_offer = 'dr_settings/config/offer_id';
        return $this->scopeConfig->getValue($dr_offer, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storecode);
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