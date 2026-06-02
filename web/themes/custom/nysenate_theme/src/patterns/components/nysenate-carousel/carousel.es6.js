/**
 * @file
 * Behaviors for the Filter Accordion.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.carousel = {
    attach: function() {
      var $sliders = $('.carousel__slick').not('.slick-initialized');
      $sliders
        .on('init', function(event, slick) {
          var $el = $(this);
          $el.removeAttr('tabindex');

          // Slick calls setSlideClasses() at multiple points (init, afterChange,
          // resize, etc.) and re-adds tabindex="0" to the active slide each time.
          // A MutationObserver removes it immediately whenever Slick sets it,
          // so slides are never in the tab order regardless of when Slick fires.
          var slideObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
              if (mutation.attributeName === 'tabindex') {
                mutation.target.removeAttribute('tabindex');
              }
            });
          });
          $el.find('.slick-slide').each(function() {
            slideObserver.observe(this, { attributes: true, attributeFilter: ['tabindex'] });
          });

          // Slick uses aria-disabled="true" on inactive prev/next buttons but
          // never sets the `disabled` attribute, so they remain focusable.
          // Watch for aria-disabled changes and mirror them to tabindex.
          function syncArrowTabindex(btn) {
            btn.tabIndex = btn.getAttribute('aria-disabled') === 'true' ? -1 : 0;
          }
          var arrowObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
              syncArrowTabindex(mutation.target);
            });
          });
          $el.find('.slick-prev, .slick-next').each(function() {
            syncArrowTabindex(this);
            arrowObserver.observe(this, { attributes: true, attributeFilter: ['aria-disabled'] });
          });
        })
        .slick({
          infinite: false,
          adaptiveHeight: true
        });
    }
  };
})(document, Drupal, jQuery);
