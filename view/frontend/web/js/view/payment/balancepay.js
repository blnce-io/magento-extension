/**
 * Balance Payments For Magento 2
 * https://www.getbalance.com/
 *
 * @category Balance
 * @package  Balancepay_Balancepay
 * @author   Developer: Pniel Cohen
 * @author   Company: Girit-Interactive (https://www.girit-tech.com/)
 *
 *
 * Balancepay js component.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'balancepay',
            component: 'Balancepay_Balancepay/js/view/payment/method-renderer/balancepay'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);