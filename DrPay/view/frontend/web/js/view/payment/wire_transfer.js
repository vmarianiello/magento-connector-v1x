/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
            drWireTransfer = 'drpay_wire_transfer';
        if (config[drWireTransfer].is_active) {
            rendererList.push(
                {
                    type: drWireTransfer,
                    component: 'Digitalriver_DrPay/js/view/payment/method-renderer/wire_transfer'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
