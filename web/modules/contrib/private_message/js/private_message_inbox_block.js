/**
 * @file
 * Adds JavaScript functionality to the private message inbox block.
 */

Drupal.PrivateMessageInbox = {};
Drupal.PrivateMessageInbox.updateInbox = {};

(($, Drupal, drupalSettings, window, once) => {
  let initialized;
  let container;
  let updateInterval;
  let loadingPrev;
  let loadingNew;

  /**
   * Used to manually trigger Drupal's JavaScript commands.
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

  /**
   * Updates the inbox after an Ajax call.
   */
  function updateInbox() {
    if (!loadingNew) {
      loadingNew = true;

      const ids = {};
      container.find('.private-message-thread-inbox').each(() => {
        ids[$(this).attr('data-thread-id')] = $(this).attr('data-last-update');
      });

      $.ajax({
        url: drupalSettings.privateMessageInboxBlock.loadNewUrl,
        method: 'POST',
        data: { ids },
        success(data) {
          loadingNew = false;
          triggerCommands(data);
          if (updateInterval) {
            window.setTimeout(updateInbox, updateInterval);
          }
        },
      });
    }
  }

  /**
   * Reorders the inbox after an Ajax Load, to show newest threads first.
   * @param {Array} threadIds The threads IDs.
   * @param {Array} newThreads The new Threads.
   */
  function reorderInbox(threadIds, newThreads) {
    const map = {};

    container.children('.private-message-thread-inbox').each(() => {
      const element = $(this);
      map[element.attr('data-thread-id')] = element;
    });

    $.each(threadIds, (index) => {
      const threadId = threadIds[index];

      if (newThreads[threadId]) {
        if (map[threadId]) {
          map[threadId].remove();
        }

        $('<div/>').html(newThreads[threadId]).contents().appendTo(container);
      } else if (map[threadId]) {
        container.append(map[threadId]);
      }
    });

    Drupal.attachBehaviors(container[0]);
  }

  /**
   * Inserts older threads into the inbox after an Ajax load.
   * @param {string} threads The threads HTML.
   */
  function insertPreviousThreads(threads) {
    const contents = $('<div/>').html(threads).contents();

    contents.css('display', 'none').appendTo(container).slideDown(300);
    Drupal.attachBehaviors(contents[0]);
  }

  /**
   * Adds CSS classes to the currently selected thread.
   * @param {string} threadId The thread id.
   */
  function setActiveThread(threadId) {
    container.find('.active-thread:first').removeClass('active-thread');
    container
      .find(`.private-message-thread[data-thread-id="${threadId}"]:first`)
      .removeClass('unread-thread')
      .addClass('active-thread');
  }

  /**
   * Click handler for the button that loads older threads into the inbox.
   * @param {Object} e The event.
   */
  function loadOldThreadWatcherHandler(e) {
    e.preventDefault();

    if (!loadingPrev) {
      loadingPrev = true;

      let oldestTimestamp;
      container.find('.private-message-thread').each(() => {
        if (
          !oldestTimestamp ||
          Number($(this).attr('data-last-update')) < oldestTimestamp
        ) {
          oldestTimestamp = Number($(this).attr('data-last-update'));
        }
      });

      $.ajax({
        url: drupalSettings.privateMessageInboxBlock.loadPrevUrl,
        data: {
          timestamp: oldestTimestamp,
          count: drupalSettings.privateMessageInboxBlock.threadCount,
        },
        success(data) {
          loadingPrev = false;
          triggerCommands(data);
        },
      });
    }
  }

  /**
   * Watches the button that loads previous threads into the inbox.
   * @param {Object} context The context.
   */
  function loadOlderThreadWatcher(context) {
    $(
      once(
        'load-loder-threads-watcher',
        '#load-previous-threads-button',
        context,
      ),
    ).each(() => {
      $(this).on('click', loadOldThreadWatcherHandler);
    });
  }

  /**
   * Click Handler executed when private message threads are clicked.
   *
   * Loads the thread into the private message window.
   * @param {Object} e The event.
   */
  const inboxThreadLinkListenerHandler = (e) => {
    if (Drupal.PrivateMessages) {
      e.preventDefault();

      Drupal.PrivateMessages.loadThread($(this).attr('data-thread-id'));
    }
  };

  /**
   * Watches private message threads for clicks, so new threads can be loaded.
   * @param {Object} context The context.
   */
  function inboxThreadLinkListener(context) {
    $(
      once(
        'inbox-thread-link-listener',
        '.private-message-inbox-thread-link',
        context,
      ),
    ).each(() => {
      $(this).click(inboxThreadLinkListenerHandler);
    });
  }

  /**
   * Initializes the private message inbox JavaScript.
   */
  function init() {
    if (!initialized) {
      initialized = true;
      container = $(
        '.block-private-message-inbox-block .private-message-thread--full-container',
      );
      if (
        drupalSettings.privateMessageInboxBlock.totalThreads >
        drupalSettings.privateMessageInboxBlock.itemsToShow
      ) {
        $('<div/>', { id: 'load-previous-threads-button-wrapper' })
          .append(
            $('<a/>', { href: '#', id: 'load-previous-threads-button' }).text(
              Drupal.t('Load Previous'),
            ),
          )
          .insertAfter(container);
        loadOlderThreadWatcher(document);
      }
      updateInterval =
        drupalSettings.privateMessageInboxBlock.ajaxRefreshRate * 1000;
      if (updateInterval) {
        window.setTimeout(updateInbox, updateInterval);
      }
    }
  }

  Drupal.behaviors.privateMessageInboxBlock = {
    attach(context) {
      window.setTimeout(init, 500);
      loadOlderThreadWatcher(context);
      inboxThreadLinkListener(context);

      Drupal.AjaxCommands.prototype.insertInboxOldPrivateMessageThreads = (
        ajax,
        response,
      ) => {
        if (response.threads) {
          insertPreviousThreads(response.threads);
        }
        if (!response.threads || !response.hasNext) {
          $('#load-previous-threads-button')
            .parent()
            .slideUp(300, () => {
              $(this).remove();
            });
        }
      };

      Drupal.AjaxCommands.prototype.privateMessageInboxUpdate = (
        ajax,
        response,
      ) => {
        reorderInbox(response.threadIds, response.newThreads);
      };

      Drupal.AjaxCommands.prototype.privateMessageTriggerInboxUpdate = () => {
        updateInbox();
      };
      if (Drupal.PrivateMessages) {
        Drupal.PrivateMessages.setActiveThread = (id) => {
          setActiveThread(id);
        };
      }

      Drupal.PrivateMessageInbox.updateInbox = () => {
        updateInbox();
      };
    },
    detatch(context) {
      $(context)
        .find('#load-previous-threads-button')
        .unbind('click', loadOldThreadWatcherHandler);
      $(context)
        .find('.private-message-inbox-thread-link')
        .unbind('click', inboxThreadLinkListenerHandler);
    },
  };
})(jQuery, Drupal, drupalSettings, window, once);
