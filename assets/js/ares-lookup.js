(function ($) {
    'use strict';

    $(document).on('blur', '#acf-field_customer_ico', function () {
        var ico = $(this).val().replace(/\s/g, '');
        if (!ico || !/^\d{8}$/.test(ico)) {
            return;
        }

        var $icoWrap = $(this).closest('.acf-input');
        var $status = $icoWrap.find('.ares-status');
        if (!$status.length) {
            $status = $('<p class="ares-status" style="margin:4px 0 0;font-style:italic;"></p>');
            $icoWrap.append($status);
        }

        $status.css('color', '#666').text('Načítám data z ARES…');

        $.post(aresLookup.ajaxUrl, {
            action: 'ares_lookup',
            nonce: aresLookup.nonce,
            ico: ico
        })
        .done(function (response) {
            if (!response.success) {
                $status.css('color', '#c00').text(response.data || 'Subjekt nenalezen.');
                return;
            }
            var d = response.data;
            if (d.name)   { $('#acf-field_customer_name').val(d.name).trigger('change'); }
            if (d.street) { $('#acf-field_customer_street').val(d.street).trigger('change'); }
            if (d.zip)    { $('#acf-field_customer_zip').val(d.zip).trigger('change'); }
            if (d.city)   { $('#acf-field_customer_city').val(d.city).trigger('change'); }
            if (d.dic)    { $('#acf-field_customer_dic').val(d.dic).trigger('change'); }
            $status.css('color', '#3a3').text('Údaje byly předvyplněny z ARES.');
            setTimeout(function () { $status.text(''); }, 5000);
        })
        .fail(function () {
            $status.css('color', '#c00').text('Chyba při komunikaci se serverem.');
        });
    });

}(jQuery));
