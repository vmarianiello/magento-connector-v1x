/**
 *
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
            drIBP = 'drpay_IBP';
        if (config[drIBP].is_active) {
            rendererList.push(
                {
                    type: drIBP,
                    component: 'Digitalriver_DrPay/js/view/payment/method-renderer/IBP'
                }
            );
        }

        /**
    * Add view logic here if needed 
    */
        return Component.extend({});
    }
);
