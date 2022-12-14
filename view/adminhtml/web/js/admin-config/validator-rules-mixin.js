define([
    'jquery'
], function ($) {
    'use strict';
    return function (target) {
        $.validator.addMethod(
            'validate-terms-options-count',
            function (value) {
                return !(value.length > 2);
            },
            $.mage.__('Select up to only 2 terms options.')
        );
        return target;
    };
});
