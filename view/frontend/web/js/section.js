define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function ($, Component, customerData, urlBuilder) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.customsection = customerData.get('custom_section');
            customerData.reload(['custom_section']);
        },

        callPopup: function () {
            var options = {
                type: 'popup',
                responsive: true,
                clickableOverlay:false,
                innerScroll: true,
                modalCloseBtnHandler: function() {
                    var customeurl = urlBuilder.build('balancepay/buyer/creditlimit');
                    $.ajax({
                        url: customeurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {}
                    });
                    this.closeModal();
                }
            };
            var customurl = urlBuilder.build('balancepay/buyer/qualify');
            $.ajax({
                url: customurl,
                type: 'POST',
                dataType: 'json',
                data: {},
                success: function (response) {
                    if (response && response.hasOwnProperty('qualificationLink') && response.qualificationLink) {
                        $("#popup-modal").empty();
                        $("#popup-modal").append('<iframe id="qualify-iframe" class="no-scroll" scrolling="no" width="100%" src="' + response.qualificationLink + '" frameborder="0" allowfullscreen=""></iframe>');
                        $('#popup-modal').modal(options).modal('openModal');
                    } else {
                        setTimeout(function() {
                            customerData.set('messages', {
                                messages: [{
                                    text: 'There was a problem starting qualification process. Please try again later or contact us.',
                                    type: 'error'
                                }]
                            });
                        },1000);
                    }
                },
                error: function (xhr, status, errorThrown) {
                    customerData.set('messages', {
                        messages: [{
                            text: 'There was a problem starting qualification process. Please try again later or contact us.',
                            type: 'error'
                        }]
                    });
                }
            });
        }
    });
});
