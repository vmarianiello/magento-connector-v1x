/**
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
/*browser:true*/

define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/view/payment/default'

], function (
    $,
    _,
    Component
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Digitalriver_DrPay/payment/apple_pay',
            code: 'drpay_apple_pay'
		},
        /**
         * Get payment name
         *
         * @returns {String}
         */
        getCode: function () {
            return this.code;
        },
        
        /**
         * Get payment description
         *
         * @returns {String}
         */
        getInstructions: function () {		
            return window.checkoutConfig.payment.instructions[this.getCode()];
        },

        /**
         * Get payment title
         *
         * @returns {String}
         */
        getTitle: function () {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        /**
        * Get Digitalriver js url
        * 
        * @returns {String}
        */
        getJsUrl: function () {
            return window.checkoutConfig.payment[this.getCode()].js_url;
        },
        /**
        * Get Digitalrive public key
        * 
        * @returns {String}
        */
        getPublicKey: function () {
            return window.checkoutConfig.payment[this.getCode()].public_key;
        },

        /**
         * Check if payment is active
         *
         * @returns {Boolean}
         */
        isActive: function () {
            var active = this.getCode() === this.isChecked();

            this.active(active);

            return active;
        },
        canMakePayment: function () {
            if(window.checkoutConfig.payment.drpay_apple_pay.can_make_payment)
            {
                return "A";
            } else{
                return "B"; 
            }
        },
        radioInit: function(){
            $(".payment-methods input:radio:first").prop("checked", true).trigger("click");
        }        
    });
});
