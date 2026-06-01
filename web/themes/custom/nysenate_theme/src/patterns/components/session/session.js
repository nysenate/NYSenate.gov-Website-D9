/**
 * @file
 * Accessibility announcements for legislation load more interactions.
 */
!((document, Drupal, $) => {
  'use strict';

  /**
   * Write a message to the appropriate aria-live region for a given view.
   *
   * Priority:
   * 1. The `.aria-announcement` in a parent `.tabs-content` — this element
   *    lives OUTSIDE individual tab panels so it is never hidden by tab
   *    switching.
   * 2. The `.legislation-results-announcement` that is a preceding sibling of
   *    the view `<div>` — used when the view is rendered outside a tabs block.
   *
   * The clear + 150 ms delay is required for Safari / VoiceOver to fire.
   */
  const announce = function ($view, message) {
    let $el = $view.closest('.tabs-content').find('.aria-announcement').first();
    if (!$el.length) {
      $el = $view.prev('.legislation-results-announcement');
    }
    if (!$el.length) {
      return;
    }
    $el[0].textContent = '';
    setTimeout(function () {
      $el[0].textContent = message;
    }, 150);
  };

  const getResultCount = function ($view) {
    return $view.find('.view-content .views-infinite-scroll-content-wrapper > div, .view-content .views-row').length;
  };

  Drupal.behaviors.sessionA11yAnnouncements = {
    attach: function (context) {
      // Views Infinite Scroll appends rows into the existing view wrapper
      // rather than replacing it, so we initialize only once and rely on a
      // MutationObserver to detect newly appended rows.
      $('.view-id-upcoming_legislation', context).each(function () {
        const $view = $(this);
        if ($view.data('a11yLoadMoreInit')) {
          return;
        }
        $view.data('a11yLoadMoreInit', true);

        const $resultsContainer = $view.find('.view-content').first();
        let previousCount = getResultCount($view);

        const loadMoreSelector = '.pager-load-more a, .pager__item.pager-load-more a, a.load-more';

        $view.on('click', loadMoreSelector, function () {
          previousCount = getResultCount($view);
          announce($view, 'Loading more legislation results.');
        });

        if ($resultsContainer.length > 0) {
          const observer = new MutationObserver(function () {
            const updatedCount = getResultCount($view);
            if (updatedCount > previousCount) {
              announce($view, updatedCount + ' legislation results shown.');
              previousCount = updatedCount;
            }
          });

          observer.observe($resultsContainer.get(0), {
            childList: true,
            subtree: true,
          });
        }
      });
    },
  };
})(document, Drupal, jQuery);
