define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ], function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'greencryptopay_merchant',
                component: 'GreenCryptoPay_Merchant/js/view/payment/method-renderer/greencryptopay-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
