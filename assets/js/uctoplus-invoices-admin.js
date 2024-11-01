(function (jQuery ) {

    'use strict';
    jQuery(document).ready(function () {
        jQuery('input[name="uctoplus_options[invoice_numbering_type]"]').change(function () {
            var numberingType = jQuery(this).val();
            if (numberingType == 'uctoplus') {
                jQuery('.invoice_number_format-wrapper').fadeOut(500);
                jQuery('.next_invoice_number-wrapper').fadeOut(500);
            } else if (numberingType == 'plugin') {
                jQuery('.invoice_number_format-wrapper').fadeIn(500);
                jQuery('.next_invoice_number-wrapper').fadeIn(500);
            }
        })
    });


})(jQuery);