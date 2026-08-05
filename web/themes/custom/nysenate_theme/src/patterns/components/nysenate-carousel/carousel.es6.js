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
      const $sliders = $('.carousel__slick').not('.slick-initialized');
      $sliders
        .on('init', function(event, slick) {
          const $el = $(this);
          $el.removeAttr('tabindex');

          // Slick.js calls initADA() immediately AFTER firing this 'init'
          // event (slick.js:1284 trigger → 1288 initADA). initADA sets
          // tabindex="0" on the active .slick-slide and tabindex="-1" on
          // others. For image-only carousels there is nothing interactive
          // inside a slide, so a keyboard focus stop on the slide container
          // is unhelpful — navigation is provided by the prev/next buttons.
          // Defer the removal via setTimeout so it runs after initADA().
          setTimeout(function() {
            $el.find('.slick-slide').removeAttr('tabindex');
          }, 0);

          // Slick marks boundary arrows (e.g. "Previous" on the first slide)
          // with aria-disabled="true" but never sets tabindex="-1", leaving
          // them reachable by Tab even though pressing them does nothing.
          // This violates WCAG 2.1 SC 2.1.1 — a keyboard user tabs to the
          // button, activates it, and receives no feedback or movement.
          //
          // There is no Slick option to fix this. Slick's updateArrows() only
          // touches tabindex when the arrows are hidden entirely (slideCount <=
          // slidesToShow); in normal boundary cases it only toggles
          // aria-disabled. This is a known upstream bug open since 2017 with
          // no fix released: https://github.com/kenwheeler/slick/issues/3268
          //
          // The MutationObserver below watches aria-disabled on the two arrow
          // buttons and mirrors it to tabindex, completing what Slick intended
          // but never implemented. It is upgrade-safe because it reacts to
          // whatever Slick writes rather than overriding internal methods.
          function syncArrowTabindex(btn) {
            btn.tabIndex = btn.getAttribute('aria-disabled') === 'true' ? -1 : 0;
          }
          const arrowObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
              syncArrowTabindex(mutation.target);
            });
          });
          $el.find('.slick-prev, .slick-next').each(function() {
            syncArrowTabindex(this);
            arrowObserver.observe(this, { attributes: true, attributeFilter: ['aria-disabled'] });
          });
        })
        .on('afterChange', function() {
          // Slick re-adds tabindex to the newly active slide; remove it again.
          $(this).find('.slick-slide').removeAttr('tabindex');
        })
        .slick({
          infinite: false,
          adaptiveHeight: true,
          focusOnChange: false
        });
    }
  };
})(document, Drupal, jQuery);
