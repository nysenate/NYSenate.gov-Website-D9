!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateTabs = {
    attach: function () {
      const tabContainer = $('.l-tab-bar');
      const tabLink =  $('.c-tab .c-tab-link');

      tabContainer.each(function () {
        const tabArrowDown = $(this).find('.c-tab--arrow');
        const tabInput = $(this).find('input');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }

        tabInput.on('click', function () {
          tabInput.removeAttr('checked');
          tabInput.parent().removeClass('active');

          $(this).attr('checked', 'checked');
          $(this).parent().addClass('active');
        });
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
