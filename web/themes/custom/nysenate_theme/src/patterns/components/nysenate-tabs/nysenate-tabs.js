!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateTabs = {
    attach: function () {
      const tabContainer = $('.l-tab-bar');
      const tabLink = $('.c-tab .c-tab-link');
      const textExpander = $('.text-expander');

      tabContainer.each(function () {
        const tabArrowDown = $(this).find('.c-tab--arrow');
        const tabInput = $(this).find('input.form-radio');

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

      // event for text expander
      if (textExpander) {
        textExpander.click(function () {
          const link = $(this);
          const expander = link.closest('.item-list').prev();
          const lineCount = expander.data('linecount');
          const anchor = expander.prev();

          expander.slideToggle(0);

          if (expander.is(':hidden')) {
            $('html,body').animate({ scrollTop: anchor.offset().top - 180 });
            link.html('View More (' + lineCount + ' Lines)');
            link.removeClass('expanded');
          }
          else {
            $('html,body').animate({ scrollTop: expander.offset().top - 180 });
            link.html('View Less');
            link.addClass('expanded');
          }
        });
      }
    },
    toggleTabDropdown: function (e) {
      e.preventDefault();

      const tab = $(this).parent('.c-tab');
      const tabBar = $(this).parents('.l-tab-bar');
      const billVersion = tab.data('version')
        ? tab.data('version').split('-')
        : null;
      const newUrl = tab.data('target');

      if (billVersion && newUrl) {
        const tabContent = tab
          .closest('.c-bill--amendment-details')
          .parent()
          .find('.tabs-content');

        history.pushState({}, 'NY State Senate Bill ' + billVersion[1], newUrl);

        tabContent.find('.active').removeClass('active');
        tabContent.find($(this).val()).addClass('active');
      }

      if (tab.hasClass('active') && !tabBar.hasClass('open')) {
        tabBar.addClass('open');
      }
      else {
        tabBar.removeClass('open');
      }
    }
  };
})(document, Drupal, jQuery);
