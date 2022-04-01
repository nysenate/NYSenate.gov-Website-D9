(function ($, Drupal) {
  Drupal.behaviors.address_autocomplete = {
    attach: function (context, settings) {

      $('input.address-line1', context).once('initiate-autocomplete').each(function () {
        var form_wrapper = $(this).closest('.js-form-wrapper');
        var ui_autocomplete = $(this).data('ui-autocomplete');

        ui_autocomplete.options.select = function (event, ui) {
          event.preventDefault();
          form_wrapper.find('input.address-line1').val(ui.item.street_name);
          form_wrapper.find('input.postal-code').val(ui.item.zip_code);
          form_wrapper.find('input.locality').val(ui.item.town_name);
        };

      });
    }
  };
}(jQuery, Drupal));
