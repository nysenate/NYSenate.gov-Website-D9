/**
 * @file
 * Behaviors for the Sticky Nav.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Sticky Nav behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.stickyNav2 = {
    attach: function () {
      const aboutPageNav = $('.c-about--nav');
      const adminToolbar = $('#toolbar-bar');
      const adminTray = $('#toolbar-item-administration-tray.toolbar-tray');

      if (adminToolbar.length > 0) {
        aboutPageNav.css('top', `${270 + adminToolbar.height() + adminTray.height() }px`);
      }
    }
  };

  // Function to insert accessibility wayfinding anchors
  const insertA11yWayfinding = function () {
    // Find all sections with IDs like section-1, section-2, etc.
    const sections = document.querySelectorAll('[id^="section-"]');
    
    sections.forEach(function (section) {
      // Extract the section number from the id (e.g., "section-1" -> "1")
      const sectionId = section.getAttribute('id');

      // Check if the visually hidden anchor already exists
      const previousElement = section.previousElementSibling;
      if (
        previousElement &&
        previousElement.matches('a.js-section-wayfinding')
      ) {
        return;
      }
      
      // Create the visually hidden anchor
      const anchor = document.createElement('a');
      anchor.classList.add('visually-hidden', 'focusable', 'skip-link', 'js-section-wayfinding');
      anchor.href = '#sticky-sidebar';
      anchor.innerHTML = Drupal.t('Return to in page menu');
      
      // Add event listener to highlight the corresponding section in the sidebar
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const sidebar = document.getElementById('sticky-sidebar');
        
        if (sidebar) {
          // Remove active class from all sidebar items
          const sidebarItems = sidebar.querySelectorAll('[data-section]');
          sidebarItems.forEach(function(item) {
            item.classList.remove('active');
          });
          
          // Add active class to the matching section
          const matchingItem = sidebar.querySelector(`[data-section="${sectionId}"]`);
          if (matchingItem) {
            matchingItem.classList.add('active');
          }
          
          // Ensure sidebar is focusable
          if (!sidebar.hasAttribute('tabindex')) {
            sidebar.setAttribute('tabindex', '-1');
          }
          
          // Focus the sidebar
          sidebar.focus();
          sidebar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });
      
      // Insert anchor directly before the section
      section.before(anchor);
    });
  };

  /**
   * Insert accessibility wayfinding anchors for section navigation
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.insertA11yWayfinding = {
    attach: function (context) {
      // Only run once on the document
      if (context !== document) {
        return;
      }
      
      insertA11yWayfinding();
    }
  };

  // Listen for nysJumpmenuInitialized event
  document.addEventListener("nysJumpmenuInitialized", function (event) {
    // policyDetailJumpMenuAlter(event.target);
    insertA11yWayfinding();
  });

})(document, Drupal, jQuery);
