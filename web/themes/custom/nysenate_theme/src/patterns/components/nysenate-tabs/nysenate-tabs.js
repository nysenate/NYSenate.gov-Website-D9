!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateTabs = {
    attach: function () {
      const tab = $('.c-tab');
      const tabContainer = $('.l-tab-bar');
      const tabInput = tab.find('input');
      const tabLink =  $('.c-tab .c-tab-link');

      tabInput
        .each(function() {
          if ($(this).attr('checked') === 'checked') {
            $(this).parent().addClass('active');
          }
        });

      tabContainer.each(function () {
        const tabArrowDown = $(this).find('.c-tab--arrow');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }
      });

      tabLink.on('click', this.toggleTabDropdown);
    },
    toggleTabDropdown: function(e) {
      e.preventDefault();

      var tab = $(this).parent('.c-tab');
      var tabBar = $(this).parents('.l-tab-bar');

      if(tab.hasClass('active') && !tabBar.hasClass('open')) {
        tabBar.addClass('open');
      }
      else {
        tabBar.removeClass('open');
      }
    },
  };
})(document, Drupal, jQuery);
