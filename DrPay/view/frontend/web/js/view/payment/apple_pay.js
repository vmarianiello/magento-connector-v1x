/**
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
/*browser:true*/
/*global define*/

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        var config = window.checkoutConfig.payment,
            drApplePay = 'drpay_apple_pay';
        if (config[drApplePay].is_active) {
            rendererList.push(
                {
                    type: drApplePay,
                    component: 'Digitalriver_DrPay/js/view/payment/method-renderer/apple_pay'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
