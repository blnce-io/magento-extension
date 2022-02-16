define([
    'jquery',
    'Magento_Ui/js/form/element/checkbox-set'
], function ($, Checkboxset) {
    'use strict';
    return Checkboxset.extend({
        onUpdate: function (value) {
            if($('.term_checkboxes input').filter(':checked').length == 3)
                $('.term_checkboxes input:not(:checked)').attr('disabled', 'disabled');
            else
                $('.term_checkboxes input').removeAttr('disabled');
            return this._super();
        }
    });
});
