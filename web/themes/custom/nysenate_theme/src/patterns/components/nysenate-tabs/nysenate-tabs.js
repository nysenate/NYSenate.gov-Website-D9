!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateTabs = {
    attach: function () {
      const tabLink =  $('.c-tab .c-tab-link');

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
