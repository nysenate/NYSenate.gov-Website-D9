!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateTabs = {
    attach: function () {
      const tabContainer = $('.l-tab-bar');
      const tabLink = $('.c-tab .c-tab-link');
      const textExpander = $('.text-expander');
      const loadMore = $('.load-more');
      const ariaAnnouncement = $('.aria-announcement');

      // Function to update aria announcement with row counts
      const updateAriaAnnouncement = function() {
        const activePanel = $('.tabs-content .content.active');
        if (activePanel.length > 0) {
          const message = `${activePanel.find('.view-header .rows-message')[0].innerHTML}`;
          ariaAnnouncement.text(message);
        }
      };

      // Update announcement on page load
      setTimeout(updateAriaAnnouncement, 500);

      tabContainer.each(function () {
        const tabArrowDown = $(this).find('.c-tab--arrow');
        const tabButton = $(this).find('button.c-tab');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }

        tabButton.each(function () {
          // Check if button has 'active' class - no need to add class to parent since button is the tab
          if ($(this).hasClass('active')) {
            // Button is already active, no additional setup needed
          }
        });

        tabButton.on('click', function () {
          const tabContent = $(this).closest('.l-row').find('.tabs-content');
          const targetPanel = $(this).val(); // Get the target panel ID from value attribute
          const tabBar = $(this).closest('.l-tab-bar');

          // Remove active state from all buttons
          tabButton.removeClass('active').attr('aria-selected', 'false');

          // Set active state on clicked button
          $(this).addClass('active').attr('aria-selected', 'true');

          // Update content visibility
          tabContent.find('.content').removeClass('active');
          tabContent.find(targetPanel).addClass('active');

          // Update aria announcement with new content row counts
          setTimeout(updateAriaAnnouncement, 100);

          // Toggle mobile dropdown behavior - only on mobile/tablet screens
          if (window.innerWidth <= 768) {
            if ($(this).hasClass('active') && !tabBar.hasClass('open')) {
              tabBar.addClass('open');
            } else {
              tabBar.removeClass('open');
            }
          } else {
            // Remove open class on desktop
            tabBar.removeClass('open');
          }
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
