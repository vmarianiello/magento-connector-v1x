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
            drDirectDebit = 'drpay_direct_debit';
        if (config[drDirectDebit].is_active) {
            rendererList.push(
                {
                    type: drDirectDebit,
                    component: 'Digitalriver_DrPay/js/view/payment/method-renderer/direct_debit'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
