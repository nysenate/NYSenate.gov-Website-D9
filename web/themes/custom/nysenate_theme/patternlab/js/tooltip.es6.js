/**
 * @file
 * Attach behaviors for the tooltip.
 */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the tooltip.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.tooltip = {
    attach: function attach() {
      var wrapper = $('.tooltip-wrapper');
      wrapper.each(function (index) {
        var currentWrapper = $(this);
        var button = currentWrapper.find('.tooltip-button');
        var tooltip = currentWrapper.find('.tooltip');
        var showEvents = ['mouseenter', 'focus'];
        var hideEvents = ['mouseleave', 'blur'];
        var newId = "tooltip-".concat(index);

        if (button && tooltip) {
          button.attr('aria-describedby', newId);
          button.attr('type', 'button');
          tooltip.attr('id', newId); // eslint-disable-next-line no-undef

          var popperInstance = Popper.createPopper(button.get(0), tooltip.get(0), {
            placement: 'bottom',
            modifiers: [{
              name: 'offset',
              options: {
                offset: [0, 8]
              }
            }]
          });

          var show = function show() {
            tooltip.attr('data-show', '');
            popperInstance.update();
          };

          var hide = function hide() {
            tooltip.removeAttr('data-show');
          };

          showEvents.forEach(function (event) {
            button.on(event, show);
          });
          hideEvents.forEach(function (event) {
            button.on(event, hide);
          });
        }
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=tooltip.es6.js.map
