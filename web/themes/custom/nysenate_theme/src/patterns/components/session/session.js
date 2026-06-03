/**
 * @file
 * Accessibility announcements for legislation load more interactions.
 */
!((document, Drupal) => {
  'use strict';

  const getResultCount = function (view) {
    return view.querySelectorAll('.view-content .views-infinite-scroll-content-wrapper > div, .view-content .views-row').length;
  };

  // WeakSet tracks initialized views so the MutationObserver and delegated
  // click listener are only attached once per element across attach() calls.
  const initializedViews = new WeakSet();

  Drupal.behaviors.sessionA11yAnnouncements = {
    attach: function (context) {
      // Views Infinite Scroll appends rows into the existing view wrapper
      // rather than replacing it, so we initialize only once and rely on a
      // MutationObserver to detect newly appended rows.
      context.querySelectorAll('.view-id-upcoming_legislation').forEach(function (view) {
        if (initializedViews.has(view)) {
          return;
        }
        initializedViews.add(view);

        const resultsContainer = view.querySelector('.view-content');
        let previousCount = getResultCount(view);

        const loadMoreSelector = '.pager-load-more a, .pager__item.pager-load-more a, a.load-more';

        // Delegated click: matches the load-more link regardless of DOM depth.
        view.addEventListener('click', function (e) {
          if (e.target.closest(loadMoreSelector)) {
            previousCount = getResultCount(view);
            Drupal.announce('Loading more legislation results.');
          }
        });

        if (resultsContainer) {
          const observer = new MutationObserver(function () {
            const updatedCount = getResultCount(view);
            if (updatedCount > previousCount) {
              Drupal.announce(updatedCount + ' legislation results shown.');
              previousCount = updatedCount;
            }
          });

          observer.observe(resultsContainer, {
            childList: true,
            subtree: true,
          });
        }
      });
    },
  };
})(document, Drupal);
