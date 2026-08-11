/**
 * @file
 * Accessibility announcements for legislation load more interactions.
 */
!((document, Drupal) => {
  'use strict';

  const getResultCount = function (view) {
    return view.querySelectorAll('.view-content .views-infinite-scroll-content-wrapper > div').length;
  };

  // WeakSet tracks initialized views so the MutationObserver and delegated
  // click listener are only attached once per element across attach() calls.
  const initializedViews = new WeakSet();

  Drupal.behaviors.sessionA11yAnnouncements = {
    attach: function (context) {
      // Views Infinite Scroll appends rows to the existing view wrapper
      // rather than replacing it. We initialize once per view element and
      // rely on a MutationObserver to detect newly appended rows.
      //
      // FOCUS LOSS: The pager containing the "Load More" link is replaced
      // after each AJAX request. This drops focus to <body>, causing
      // VoiceOver to announce the page title and landmark context.
      // Fix: move focus to a silent aria-hidden anchor BEFORE AJAX fires,
      // then restore it to the first new row in the MutationObserver callback.

      // querySelectorAll returns nothing if context IS the view element
      // (you cannot query-select yourself), which is the case when Views
      // Infinite Scroll calls attachBehaviors after appending rows. That is
      // intentional — the MutationObserver is already watching at that point.
      context.querySelectorAll('.view-id-upcoming_legislation').forEach(function (view) {
        if (initializedViews.has(view)) {
          return;
        }
        initializedViews.add(view);

        // Silent focus anchor: aria-hidden so VoiceOver ignores it when
        // focus lands here between the click and the new rows appearing.
        const focusHolder = document.createElement('div');
        // WAI-ARIA 1.2: aria-hidden MUST NOT be set on focusable elements
        // (including tabindex="-1"). Omitting it here; CSS visual hiding is
        // sufficient to keep this anchor invisible to sighted users, and
        // VoiceOver will announce nothing for an empty, unlabeled element.
        focusHolder.setAttribute('tabindex', '-1');
        focusHolder.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;';
        view.parentNode.insertBefore(focusHolder, view.nextSibling);

        const wrapper = view.querySelector('.view-content .views-infinite-scroll-content-wrapper');
        let previousCount = getResultCount(view);

        const loadMoreSelector = '.pager-load-more a, .pager__item.pager-load-more a, a.load-more';

        view.addEventListener('click', function (e) {
          if (e.target.closest(loadMoreSelector)) {
            previousCount = getResultCount(view);
            // Move focus to the silent anchor BEFORE AJAX removes the
            // Load More link from the DOM, preventing focus from dropping
            // to <body> and triggering VoiceOver's page-title announcement.
            focusHolder.focus({ preventScroll: true });
            Drupal.announce('Loading more legislation results.');
          }
        });

        if (wrapper) {
          const observer = new MutationObserver(function () {
            const updatedCount = getResultCount(view);
            if (updatedCount > previousCount) {
              // Focus the first newly appended row so keyboard users land
              // at the new content rather than remaining on the silent anchor.
              const firstNewRow = wrapper.children[previousCount];
              if (firstNewRow) {
                firstNewRow.setAttribute('tabindex', '-1');
                firstNewRow.focus({ preventScroll: true });
              }
              Drupal.announce(updatedCount + ' legislation results now shown.');
              previousCount = updatedCount;
            }
          });

          observer.observe(wrapper, {
            childList: true,
          });
        }
      });
    },
  };
})(document, Drupal);
