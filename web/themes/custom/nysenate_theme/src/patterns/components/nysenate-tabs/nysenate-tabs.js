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
        const activeTab = $('.l-tab-bar button.c-tab.active');
        
        if (activePanel.length > 0 && activeTab.length > 0) {
          const rowsMessageElement = activePanel.find('.view-header .rows-message')[0];
          const tabName = activeTab.text().trim();
          
          if (rowsMessageElement) {
            const rowsMessage = rowsMessageElement.innerHTML;
            const fullMessage = tabName + ' tab. ' + rowsMessage;
            
            // Clear and reset to force screen reader announcement
            ariaAnnouncement.text('');
            setTimeout(function() {
              ariaAnnouncement.text(fullMessage);
            }, 50);
          }
        }
      };

      // Function to save active tab to sessionStorage
      const saveActiveTab = function(tabId) {
        sessionStorage.setItem('activeNewsTab', tabId);
      };

      // Function to restore active tab from sessionStorage
      const restoreActiveTab = function() {
        const savedTabId = sessionStorage.getItem('activeNewsTab');
        if (savedTabId) {
          const savedButton = $(savedTabId);
          if (savedButton.length > 0) {
            // Remove active from all tabs
            $('.l-tab-bar button.c-tab').removeClass('active').attr('aria-selected', 'false').attr('aria-expanded', 'false');
            $('.tabs-content .content').removeClass('active');
            
            // Set active on saved tab
            savedButton.addClass('active').attr('aria-selected', 'true').attr('aria-expanded', 'true');
            const targetPanel = savedButton.val();
            $(targetPanel).addClass('active');
          }
        }
      };

      // Restore active tab on page load
      restoreActiveTab();
      
      // Always announce on page load, after tab restoration and DOM is ready
      setTimeout(function() {
        updateAriaAnnouncement();
      }, 200);

      tabContainer.each(function () {
        const tabArrowDown = $(this).find('.c-tab--arrow');
        const tabButton = $(this).find('button.c-tab');
        const tabInput = $(this).find('input.form-radio');

        if (tabArrowDown.length < 1) {
          $(this).append('<div class="c-tab--arrow u-mobile-only"></div>');
        }

        // Handle button-based tabs (new accessible pattern)
        if (tabButton.length > 0) {
          tabButton.each(function () {
            // Set aria-controls to the panel ID (removing the # from the value)
            const panelId = $(this).val().replace('#', '');
            $(this).attr('aria-controls', panelId);
            
            // Set proper aria-selected based on active class
            if ($(this).hasClass('active')) {
              $(this).attr('aria-selected', 'true');
              $(this).attr('aria-expanded', 'true');
            } else {
              $(this).attr('aria-selected', 'false');
              $(this).attr('aria-expanded', 'false');
            }
          });

          tabButton.on('click', function () {
            const tabContent = $(this).closest('.l-row').find('.tabs-content');
            const targetPanel = $(this).val(); // Get the target panel ID from value attribute
            const tabBar = $(this).closest('.l-tab-bar');
            const tabId = '#' + $(this).attr('id'); // Save the button ID

            // Save active tab to sessionStorage
            saveActiveTab(tabId);

            // Remove active state from all buttons
            tabButton.removeClass('active').attr('aria-selected', 'false').attr('aria-expanded', 'false');

            // Set active state on clicked button
            $(this).addClass('active').attr('aria-selected', 'true').attr('aria-expanded', 'true');

            // Update content visibility
            tabContent.find('.content').removeClass('active');
            tabContent.find(targetPanel).addClass('active');

            // Update aria announcement with new content row counts
            setTimeout(updateAriaAnnouncement, 50);

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
        }

        // Handle input-based tabs (legacy pattern for views exposed forms)
        // Matches the behavior from commit 6d96623f7
        if (tabInput.length > 0) {
          tabInput.each(function () {
            // Use .attr('checked') instead of .is(:checked)
            if ($(this).attr('checked')) {
              $(this).parent().addClass('active');
            }
          });

          tabInput.on('click', function () {
            const tabInputContainer = tabInput.parent();
            
            // Remove active state from all input tabs
            tabInput.removeAttr('checked');
            tabInputContainer.removeClass('active');

            // Set active state on clicked input
            $(this).attr('checked', 'checked');
            $(this).parent().addClass('active');
            
            // For views exposed forms, trigger form submission for BEF auto-submit
            const $form = $(this).closest('form');
            if ($form.hasClass('views-exposed-form')) {
              // The radio button change will trigger BEF auto-submit naturally
              // No need to manually trigger submit
            }
          });
        }
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
