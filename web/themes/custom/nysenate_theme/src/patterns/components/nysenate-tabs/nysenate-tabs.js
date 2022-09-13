!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateTabs = {
    attach: function () {
      const tabContainer = $('.l-tab-bar');
      const tabLink = $('.c-tab .c-tab-link');

      tabContainer.each(function () {
        const tabArrowDown = $(this).find('.c-tab--arrow');
        const tabInput = $(this).find('input.form-radio');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }

        tabInput.each(function () {
          if ($(this).is(':checked')) {
            $(this).parent().addClass('active');
          }
        });

        tabInput.on('click', function () {
          const tabInputContainer = tabInput.parent();
          const tabContent = tabInputContainer
            .parent()
            .parent()
            .find('.tabs-content');

          tabInput.removeAttr('checked');
          tabInputContainer.removeClass('active');

          $(this).attr('checked', 'checked');
          $(this).parent().addClass('active');

          tabContent.find('.active').removeClass('active');
          tabContent.find($(this).val()).addClass('active');
        });
      });

      tabLink.on('click', this.toggleTabDropdown);
    },
    toggleTabDropdown: function (e) {
      e.preventDefault();

      var tab = $(this).parent('.c-tab');
      var tabBar = $(this).parents('.l-tab-bar');

      if (tab.hasClass('active') && !tabBar.hasClass('open')) {
        tabBar.addClass('open');
      }
      else {
        tabBar.removeClass('open');
      }
    }
  };
})(document, Drupal, jQuery);
