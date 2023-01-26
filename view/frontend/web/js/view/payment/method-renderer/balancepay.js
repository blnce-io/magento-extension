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
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Paypal/js/action/set-payment-method',
        'Magento_Customer/js/customer-data',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Ui/js/model/messages',
        'mage/translate',
        'mage/validation',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function(
        $,
        Component,
        additionalValidators,
        redirectOnSuccessAction,
        setPaymentMethodAction,
        customerData,
        ko,
        quote,
        billingAddress,
        Messages,
        $t,
        validation,
        fullScreenLoader
    ) {
        'use strict';

        var self = null;

        return Component.extend({
            defaults: {
                template: 'Balancepay_Balancepay/payment/balancepay',
                balanceSdkLoaded: false,
                reservedOrderId: '',
                balanceSdkUrl: '',
                balanceIframeUrl: '',
                balanceCheckoutTokenUrl: '',
                balancelogoImageUrl: '',
                balanceIsAuth: false,
                balanceSource: '',
            },

            initObservable: function() {
                self = this;

                self._super()
                    .observe([
                        'balanceSdkLoaded'
                    ]);

                this.messageContainer = new Messages();

                //Load Balance SDK script
                var balanceSdk = document.createElement('script');
                balanceSdk.onload = function() {
                    self.balanceSdkLoaded(true);
                };
                balanceSdk.src = self.getBalanceSdkUrl();
                document.head.appendChild(balanceSdk);

                return self;
            },

            /**
             * @returns {String}
             */
            getCode: function() {
                return 'balancepay';
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function() {
                return true;
            },

            isShowLegend: function() {
                return true;
            },

            /** Returns is method available */
            isAvailable: function() {
                return true;
            },

            context: function() {
                return self;
            },

            getBalanceSdkUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].balanceSdkUrl;
            },

            getBalanceIframeUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].balanceIframeUrl;
            },

            getBalanceCheckoutTokenUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].balanceCheckoutTokenUrl;
            },

            getBalancelogoImageUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].balancelogoImageUrl;
            },

            getBalanceIsAuth: function() {
                return window.checkoutConfig.payment[self.getCode()].balanceIsAuth;
            },

            getBalanceSource: function() {
                return window.checkoutConfig.payment[self.getCode()].balanceSource;
            },

            /**
             * @return {Boolean}
             */
            validate: function() {
                return true;
            },

            getCustomerEmail: function() {
                if (quote.guestEmail) {
                    return quote.guestEmail;
                } else {
                    return customerData.email;
                }
            },

            openCheckout: function(checkoutToken) {
                window.balanceSDK.checkout
                    .init({
                        source: self.getBalanceSource(),
                        isAuth: self.getBalanceIsAuth(),
                        hideDueDate: false,
                        allowedPaymentMethods: null,
                        skipSuccessPage: false,
                        // The token that returned from the server API
                        checkoutToken,
                        url: self.getBalanceIframeUrl(),
                        type: 'checkout',
                        hideBackOnFirstScreen: false,
                        logoURL: self.getBalancelogoImageUrl(),
                        onSuccess: () => {
                            console.log('Balancepay onSuccess');
                            self.getPlaceOrderDeferredObject()
                                .fail(
                                    function() {
                                        self.isPlaceOrderActionAllowed(true);
                                        $('body').trigger('processStop');
                                    }
                                ).done(
                                    function() {
                                        self.afterPlaceOrder();
                                        if (self.redirectAfterPlaceOrder) {
                                            redirectOnSuccessAction.execute();
                                        }
                                    }
                                );
                        },
                        callback: (err, msg) => {
                            console.log('Balancepay callback message: ', msg);
                            console.error('Balancepay callback error: ', err);
                        },
                        onError: (err) => {
                            console.error('Balancepay onError: ', err);
                        },
                        onCancel: () => {
                            console.error('Balancepay onCancel');
                        },
                        onClose: () => {
                            console.log('Balancepay onClose');
                            window.balanceSDK.checkout.destroy();
                            $('body').trigger('processStop');
                            self.isPlaceOrderActionAllowed(true);
                        },
                    });
                    window.balanceSDK.checkout.render(checkoutToken, '#balance-checkout');
            },

            placeOrder: function(data, event) {
                if (event) {
                    event.preventDefault();
                }
                if (!self.balanceSdkLoaded(true)) {
                    var message = 'Balance SDK script is missing. Please try to refresh the page and contact us if it doesn\'t help.';
                    console.error(message);
                    self.messageContainer.addErrorMessage({
                        message: $t(message)
                    });
                    return false;
                }

                $('body').trigger('processStart');
                self.isPlaceOrderActionAllowed(false);

                $.ajax({
                    url: self.getBalanceCheckoutTokenUrl(),
                    method: 'get',
                    data: {
                        email: self.getCustomerEmail()
                    },
                    cache: false
                }).always(function(res) {
                    if (res && !res.error && res.token) {
                        self.openCheckout(res.token);
                    } else {
                        console.error(res);
                        self.messageContainer.addErrorMessage({
                            message: $t(res.message || "An error occurred on while trying to initialize Balance payment.")
                        });
                        $('body').trigger('processStop');
                        self.isPlaceOrderActionAllowed(true);
                    }
                });
            }
        });
    }
);
