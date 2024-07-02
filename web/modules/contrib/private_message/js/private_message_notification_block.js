/**
 * @file
 * JavaScript functionality for the private message notification block.
 */

Drupal.PrivateMessageNotificationBlock = {};

(($, Drupal, drupalSettings, window) => {
  let initialized;
  let notificationWrapper;
  let refreshRate;
  let checkingCount;

  /**
   * Trigger Ajax Commands.
   * @param {Object} data The data.
   */
  function triggerCommands(data) {
    const ajaxObject = Drupal.ajax({
      url: '',
      base: false,
      element: false,
      progress: false,
    });

    // Trigger any any ajax commands in the response.
    ajaxObject.success(data, 'success');
  }

  function updateCount(unreadItemsCount) {
    notificationWrapper = $('.private-message-notification-wrapper');

    if (unreadItemsCount) {
      notificationWrapper.addClass('unread-threads');
    } else {
      notificationWrapper.removeClass('unread-threads');
    }

    notificationWrapper
      .find('.private-message-page-link')
      .text(unreadItemsCount);

    // Get the current page title.
    let pageTitle = $('head title').text();
    // Check if there are any unread threads.
    if (unreadItemsCount) {
      // Check if the unread thread count is already in the page title.
      if (pageTitle.match(/^\(\d+\)\s/)) {
        // Update the unread thread count in the page title.
        pageTitle = pageTitle.replace(/^\(\d+\)\s/, `(${unreadItemsCount}) `);
      } else {
        // Add the unread thread count to the URL.
        pageTitle = `(${unreadItemsCount}) ${pageTitle}`;
      }
    }
    // No unread messages.
    // Check if thread count currently exists in the page title.
    else if (pageTitle.match(/^\(\d+\)\s/)) {
      // Remove the unread thread count from the page title.
      pageTitle = pageTitle.replace(/^\(\d+\)\s/, '');
    }

    // Set the updated title.
    $('head title').text(pageTitle);
  }

  /**
   * Retrieve the new unread thread count from the server using AJAX.
   */
  function getUnreadItemsCount() {
    if (!checkingCount) {
      checkingCount = true;

      $.ajax({
        url: drupalSettings.privateMessageNotificationBlock
          .newMessageCountCallback,
        success(data) {
          triggerCommands(data);

          checkingCount = false;
          if (refreshRate) {
            window.setTimeout(getUnreadItemsCount, refreshRate);
          }
        },
      });
    }
  }

  Drupal.PrivateMessageNotificationBlock.getUnreadItemsCount = () => {
    getUnreadItemsCount();
  };

  /**
   * Initializes the script.
   */
  function init() {
    if (!initialized) {
      initialized = true;

      if (drupalSettings.privateMessageNotificationBlock.ajaxRefreshRate) {
        refreshRate =
          drupalSettings.privateMessageNotificationBlock.ajaxRefreshRate * 1000;
        if (refreshRate) {
          window.setTimeout(getUnreadItemsCount, refreshRate);
        }
      }
    }
  }

  Drupal.behaviors.privateMessageNotificationBlock = {
    attach() {
      init();

      Drupal.AjaxCommands.prototype.privateMessageUpdateUnreadItemsCount = (
        ajax,
        response,
      ) => {
        updateCount(response.unreadItemsCount);
      };
    },
  };
})(jQuery, Drupal, drupalSettings, window);
