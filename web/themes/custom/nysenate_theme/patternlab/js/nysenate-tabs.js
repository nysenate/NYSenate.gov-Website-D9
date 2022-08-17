!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysenateTabs = {
    attach: function attach() {
      var tab = $('.c-tab');
      var tabContainer = $('.l-tab-bar');
      var tabInput = tab.find('input');
      var tabLink = $('.c-tab .c-tab-link');
      tabInput.each(function () {
        if ($(this).attr('checked') === 'checked') {
          $(this).parent().addClass('active');
        }
      });
      tabContainer.each(function () {
        var tabArrowDown = $(this).find('.c-tab--arrow');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }
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
