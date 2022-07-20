!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.micrositeMenu = {
    attach: function (context) {
      let self = this;
      const menu = $('.c-nav--wrap', context);
      const searchToggle = $('.js-search--toggle', context);

      searchToggle.on('click touch', function() {
        self.toggleSearchBar(menu);
      });
    },
    toggleSearchBar: function(menu) {
      if(menu.hasClass('search-open')) {
        menu.removeClass('search-open');
        menu.find('.c-site-search--box').blur();
        $('.c-site-search').removeClass('open');
        $('.c-site-search').blur();
      }
      else {
        menu.addClass('search-open');
        menu.find('.c-site-search--box').focus();
        $('.c-site-search').addClass('open');
        $('.c-site-search').find('.c-site-search--box').focus();
      }
    },
    closeSearch: function() {

      if($('.c-nav--wrap').hasClass('search-open')) {
        $('.c-nav--wrap').removeClass('search-open');
        $('.c-nav--wrap').find('.c-site-search--box').blur();
      }
    }
  };
})(document, Drupal, jQuery);
