!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysenateTabs = {
    attach: function attach() {
      var tabContainer = $('.l-tab-bar');
      var tabLink = $('.c-tab .c-tab-link');
      var textExpander = $('.text-expander');
      var loadMore = $('.load-more');
      tabContainer.each(function () {
        var tabArrowDown = $(this).find('.c-tab--arrow');
        var tabInput = $(this).find('input.form-radio');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }

        tabInput.each(function () {
          // Use .attr('checked') instead of .is(:checked), because
          // tabInput.on('click') logic below operates on checked attribute.
          if ($(this).attr('checked')) {
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
      tabLink.on('click', this.toggleTabDropdown); // event for text expander

      if (textExpander) {
        textExpander.click(function () {
          var link = $(this);
          var expander = link.closest('.item-list').prev();
          var lineCount = expander.data('linecount');
          var anchor = expander.prev();
          expander.slideToggle(0);

          if (expander.is(':hidden')) {
            $('html,body').animate({
              scrollTop: anchor.offset().top - 180
            });
            link.html('View More (' + lineCount + ' Lines)');
            link.removeClass('expanded');
          } else {
            $('html,body').animate({
              scrollTop: expander.offset().top - 180
            });
            link.html('View Less');
            link.addClass('expanded');
          }
        });
      } // event for load more


      if (loadMore) {
        var animationDelay = 200;
        var animationDuration = 400;
        loadMore.each(function () {
          var pagerContainer = $(this).closest('.item-list');
          var items = pagerContainer.parent().find('.content__item');
          var limit = parseInt(pagerContainer.data('limit')) || 5;
          items.css('display', 'none');
          items.slice(0, limit).show();
          var itemsHidden = $(this).closest('.item-list').parent().find('.content__item:hidden');
          $(this).on('click', function (e) {
            var _this = this;

            e.preventDefault();
            itemsHidden.slice(0, limit).delay(animationDelay).slideDown(animationDuration, function () {
              itemsHidden = $(_this).closest('.item-list').parent().find('.content__item:hidden');

              if (itemsHidden.length === 0) {
                $(_this).css('display', 'none');
              }
            });
          });
        });
      }
    },
    toggleTabDropdown: function toggleTabDropdown(e) {
      e.preventDefault();
      var tab = $(this).parent('.c-tab');
      var tabBar = $(this).parents('.l-tab-bar');
      var billVersion = tab.data('version') ? tab.data('version').split('-') : null;
      var newUrl = tab.data('target');

      if (billVersion && newUrl) {
        var tabContent = tab.closest('.c-bill--amendment-details').parent().find('.tabs-content');
        history.pushState({}, 'NY State Senate Bill ' + billVersion[1], newUrl);
        tabContent.find('.active').removeClass('active');
        tabContent.find($(this).val()).addClass('active');
      }

      if (tab.hasClass('active') && !tabBar.hasClass('open')) {
        tabBar.addClass('open');
      } else {
        tabBar.removeClass('open');
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-tabs.js.map
