/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/billing-address': {
                'Digitalriver_DrPay/js/view/billing-address': true
            },
            'Magento_Checkout/js/view/payment': {
                'Digitalriver_DrPay/js/view/payment': true
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'Digitalriver_DrPay/js/view/shipping-address/address-renderer/default': true
            },
            'Magento_Checkout/js/action/get-payment-information': {
                'Digitalriver_DrPay/js/action/get-payment-information-mixin': true
            },
			'Magento_Checkout/js/action/set-shipping-information': {
                'Digitalriver_DrPay/js/action/set-shipping-information-mixin': true
            }
        }
    },    
    map: {
        '*': {
            'Magento_Checkout/js/model/step-navigator': 'Digitalriver_DrPay/js/model/step-navigator'
        }
    }
};

