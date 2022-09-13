!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysenateTabs = {
    attach: function attach() {
      var tabContainer = $('.l-tab-bar');
      var tabLink = $('.c-tab .c-tab-link');
      tabContainer.each(function () {
        var tabArrowDown = $(this).find('.c-tab--arrow');
        var tabInput = $(this).find('input.form-radio');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }

        tabInput.each(function () {
          if ($(this).is(':checked')) {
            $(this).parent().addClass('active');
          }
        });
        tabInput.on('click', function () {
          var tabInputContainer = tabInput.parent();
          var tabContent = tabInputContainer.parent().parent().find('.tabs-content');
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
    toggleTabDropdown: function toggleTabDropdown(e) {
      e.preventDefault();
      var tab = $(this).parent('.c-tab');
      var tabBar = $(this).parents('.l-tab-bar');

      if (tab.hasClass('active') && !tabBar.hasClass('open')) {
        tabBar.addClass('open');
      } else {
        tabBar.removeClass('open');
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-tabs.js.map
