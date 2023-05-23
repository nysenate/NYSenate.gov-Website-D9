/**
 * @file
 * Attach behaviors for the tooltip.
 */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the tooltip.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tooltip = {
    attach: function () {
      const wrapper = $('.tooltip-wrapper');

      wrapper.each(function (index) {
        const currentWrapper = $(this);
        const button = currentWrapper.find('.tooltip-button');
        const tooltip = currentWrapper.find('.tooltip');
        const showEvents = ['mouseenter', 'focus'];
        const hideEvents = ['mouseleave', 'blur'];
        const newId = `tooltip-${index}`;

        if (button && tooltip) {
          button.attr('aria-describedby', newId);
          button.attr('type', 'button');
          tooltip.attr('id', newId);

          // eslint-disable-next-line no-undef
          const popperInstance = Popper.createPopper(
            button.get(0),
            tooltip.get(0),
            {
              placement: 'bottom',
              modifiers: [
                {
                  name: 'offset',
                  options: {
                    offset: [0, 8]
                  }
                }
              ]
            }
          );
          const show = function () {
            tooltip.attr('data-show', '');
            popperInstance.update();
          };
          const hide = function () {
            tooltip.removeAttr('data-show');
          };

          showEvents.forEach((event) => {
            button.on(event, show);
          });

          hideEvents.forEach((event) => {
            button.on(event, hide);
          });
        }
      });
    }
  };
})(document, Drupal, jQuery);
