/**
 * @file
 * Adds JavaScript functionality to the uninstallation preparation confirm page.
 */

(($, Drupal, window, once) => {
  function uninstallButtonWatcher(context) {
    $(
      once(
        'uninstall-button-watcher',
        '#private-message-admin-uninstall-form #edit-submit',
        context,
      ),
    ).each(() => {
      $(this).click(() => {
        return window.confirm(
          Drupal.t(
            'This will delete all private message content from the database. Are you absolutely sure you wish to proceed?',
          ),
        );
      });
    });
  }

  Drupal.behaviors.privateMessageUninstallPrepConfirm = {
    attach: (context) => {
      uninstallButtonWatcher(context);
    },
  };
})(jQuery, Drupal, window, once);
