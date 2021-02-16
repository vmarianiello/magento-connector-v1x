<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Plugin;

class AccountManagementPlugin
{

    /**
     * @var session
     */
    protected $session;
    
    public function __construct(
        \Magento\Checkout\Model\Session $session
    ) {
         $this->session = $session;
    }

    /**
     * Set guest email
     *
     * @param  \Magento\Customer\Model\AccountManagement               $subject
     * @param  $customerEmail
     * @param  $websiteId
     * @return null
     */
    public function beforeIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $subject,
        $customerEmail,
        $websiteId = null
    ) {
        if ($customerEmail) {
            $this->session->setGuestCustomerEmail($customerEmail);
        }
    }
}
