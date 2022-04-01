/**
 * @file
 * Javascript for the Geolocation location input.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Generic behavior.
   *
   * @type {Drupal~behavior}
   * @type {Object} drupalSettings.geolocation
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches functionality to relevant elements.
   */
  Drupal.behaviors.locationInputGeocoder = {
    attach: function (context, drupalSettings) {
      $.each(drupalSettings.geolocation.locationInput.geocoder, function (index, settings) {
        var input = $('.location-input-geocoder.' + settings.identifier, context).once('location-input-geocoder-processed').first();
        if (input.length) {
          if (settings.hideForm) {
            input.hide();
          }

          var latitudeInput = input.find('input.geolocation-input-latitude').first();
          var longitudeInput = input.find('input.geolocation-input-longitude').first();

          Drupal.geolocation.geocoder.addResultCallback(function (address) {
            if (typeof address.geometry.location === 'undefined') {
              return false;
            }
            latitudeInput.val(address.geometry.location.lat());
            longitudeInput.val(address.geometry.location.lng());

            if (settings.autoSubmit) {
              input.closest('form').find('input.js-form-submit').first().click();
            }
          }, settings.identifier);

          Drupal.geolocation.geocoder.addClearCallback(function () {
            latitudeInput.val('');
            longitudeInput.val('');
          }, settings.identifier);
        }
      });
    }
  };

})(jQuery, Drupal);
