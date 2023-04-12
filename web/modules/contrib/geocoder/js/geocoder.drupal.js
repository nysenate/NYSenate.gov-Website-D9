/**
 * @file
 * Javascript for the Geocoder Origin Autocomplete.
 */

(function($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.geocode_origin_autocomplete = {
    attach: function(context, settings) {

      function geocode (address, providers, address_format) {
        const base_url = drupalSettings.path.baseUrl;
        const geocode_path = base_url + 'geocoder/api/geocode';
        const address_format_query_url = address_format === null ? '' : '&address_format=' + address_format;
        return $.ajax({
          url: geocode_path + '?address=' +  encodeURIComponent(address) + '&geocoder=' + providers + address_format_query_url,
          type:"GET",
          contentType:"application/json; charset=utf-8",
          dataType: "json",
        });
      }

      // Run filters on page load if state is saved by browser.
      once('autocomplete-enabled', '.origin-address-autocomplete .address-input', context).forEach(function (element) {
        const providers = settings.geocode_origin_autocomplete['providers'].toString();
        const address_format = settings.geocode_origin_autocomplete['address_format'];
        $(element).autocomplete({
          autoFocus: true,
          minLength: settings.geocode_origin_autocomplete['minTerms'] || 4,
          delay: settings.geocode_origin_autocomplete.delay || 800,
          // This bit uses the geocoder to fetch address values.
          source: function (request, response) {
            let thisElement = this.element;
            thisElement.addClass('ui-autocomplete-loading');
            // Execute the geocoder.
            $.when(geocode(request.term, providers, address_format).then(
              // On Resolve/Success.
              function (results) {
                response($.map(results, function (item) {
                  thisElement.removeClass('ui-autocomplete-loading');
                  return {
                    // the value property is needed to be passed to the select.
                    value: item['formatted_address'],
                  };
                }));
              },
              // On Reject/Error.
              function() {
                response(function(){
                  return false;
                });
              }));
          }
        }).addClass('form-autocomplete');
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
