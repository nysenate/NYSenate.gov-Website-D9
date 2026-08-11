/**
 * @file
 * Accessibility announcements for senator list filter interactions.
 */
/* eslint-disable max-len */
!((document, Drupal, $) => {
  'use strict';

  // The filter <select> elements live inside the view wrapper that Drupal's
  // Views AJAX replaces entirely on each filter change.
  //
  // Two problems and their fixes:
  //
  // 1. FOCUS LOSS: Removing a focused element from the DOM causes the browser
  //    to move focus to <body>. VoiceOver then announces the page title and
  //    landmark context. Fix: move focus to a silent anchor element BEFORE
  //    AJAX fires, then restore it to the refreshed filter in re-attach().
  //
  //    The anchor uses aria-hidden="true" so VoiceOver does not announce it
  //    when focus moves to it programmatically — the focus shift is silent.
  //
  // 2. STALE ID SELECTORS: Drupal appends random suffixes to exposed form
  //    element IDs after each AJAX refresh (e.g. "edit-field-party-value"
  //    becomes "edit-field-party-value--LkQFOZhG0Oc"). Using #id selectors
  //    therefore only works on the initial page load. Fix: use the stable
  //    name attributes ("senator_committee_filter", "field_party_value")
  //    for both event delegation and post-AJAX focus restoration.

  let lastFocusedFilterName = null;
  let listenersAttached = false;

  // Silent focus anchor appended to <body> — outside any AJAX-replaced
  // content. aria-hidden prevents VoiceOver from announcing it on focus.
  let focusHolder = null;

  /**
   * Setup and attach the Senator List behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.senatorList = {
    attach: function(context) {
      // After a Views AJAX refresh Drupal calls attach() with the newly
      // inserted view element as context.
      if (context !== document) {
        const view = (context.classList && context.classList.contains('view-id-senator_and_committee_lists'))
          ? context
          : (context.querySelector && context.querySelector('.view-id-senator_and_committee_lists'));
        if (!view) return;

        // Restore focus to the filter that triggered this refresh.
        // Use name attribute because IDs get random suffixes after each AJAX.
        if (lastFocusedFilterName) {
          const target = view.querySelector('[name="' + lastFocusedFilterName + '"]');
          if (target) target.focus({ preventScroll: true });
          lastFocusedFilterName = null;
        }

        // Announce result count using the senator card selector from the
        // fields template (views-view-fields--senator-and-committee-lists).
        const count = view.querySelectorAll('.c-senator-block').length;
        if (view.querySelector('.view-empty')) {
          Drupal.announce('Results updated: no senators match the selected filters.');
        }
        else {
          Drupal.announce('Results updated: ' + count + ' senator' + (count !== 1 ? 's' : '') + ' shown.');
        }
        return;
      }

      // Initial page load only.
      if (listenersAttached) return;
      listenersAttached = true;

      // Silent focus anchor outside the AJAX-replaced zone.
      // WAI-ARIA 1.2: aria-hidden MUST NOT be set on focusable elements
      // (including tabindex="-1"). Omitting it here; CSS visual hiding is
      // sufficient to keep this anchor invisible to sighted users, and
      // VoiceOver will announce nothing for an empty, unlabeled element.
      focusHolder = document.createElement('div');
      focusHolder.setAttribute('tabindex', '-1');
      focusHolder.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;';
      document.body.appendChild(focusHolder);

      // Delegate using name selectors — stable across AJAX ID refreshes.
      // Views auto-submits the form on change (submit button has js-hide),
      // so we do not need to trigger submit ourselves.
      $(document).on('change.senatorList', 'select[name="senator_committee_filter"], select[name="field_party_value"]', function () {
        lastFocusedFilterName = this.name;
        // Move focus to the silent anchor BEFORE AJAX fires to prevent the
        // browser from dropping focus to <body> when the select is removed.
        focusHolder.focus({ preventScroll: true });
      });
    },
  };
})(document, Drupal, jQuery);
