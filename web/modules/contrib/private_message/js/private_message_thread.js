/**
 * @file
 * Adds JavaScript functionality to priveate message threads.
 */

Drupal.PrivateMessages = {};
Drupal.PrivateMessages.threadChange = {};

(($, Drupal, drupalSettings, window, once) => {
  let initialized;
  let $threadWrapper;
  let currentThreadId;
  let originalThreadId;
  let loadingPrev;
  let loadingNew;
  let container;
  let timeout;
  let refreshRate;
  let dimmer;
  let loadingThread;

  /**
   * Triggers AJAX commands when they happen outside the Form API framework.
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

  function showDimmer(callback) {
    if (!dimmer) {
      dimmer = $('<div/>', { id: 'private-message-thread-dimmer' }).appendTo(
        $threadWrapper,
      );
    }

    dimmer.fadeTo(500, 0.8, callback);
  }

  function hideDimmer() {
    if (dimmer) {
      dimmer.fadeOut(500);
    }
  }

  /**
   * Click handler for the button to load previous private messages.
   * @param {Object} e The event.
   */
  const loadPreviousListenerHandler = (e) => {
    e.preventDefault();

    // Ensure that a load isn't already in progress.
    if (!loadingPrev) {
      loadingPrev = true;

      let oldestId;

      // Get the thread ID.
      const threadId = $threadWrapper
        .children('.private-message-thread:first')
        .attr('data-thread-id');

      // Get the ID of the oldest message. This will be used for reference to
      // tell the server which messages it should send back.
      container.find('.private-message').each(() => {
        if (!oldestId || Number($(this).attr('data-message-id')) < oldestId) {
          oldestId = Number($(this).attr('data-message-id'));
        }
      });

      // Retrieve messages from the server with an AJAX callback.
      $.ajax({
        url: drupalSettings.privateMessageThread.previousMessageCheckUrl,
        data: { threadid: threadId, messageid: oldestId },
        success(data) {
          loadingPrev = false;
          // Trigger the AJAX commands that were returned from the server.
          triggerCommands(data);
        },
      });
    }
  };

  /**
   * Attaches event handlers to the load previous messages button.
   * @param {Object} context The context.
   */
  function loadPreviousListener(context) {
    $(
      once(
        'load-previous-private-messages-listener',
        '#load-previous-messages',
        context,
      ),
    ).each(() => {
      $(this).click(loadPreviousListenerHandler);
    });
  }

  /**
   * Function to attach behaviors to HTML.
   * @param {string} html The HTML.
   */
  function htmlAttachBehaviors(html) {
    // Find the node element when Twig debug is enabled.
    for (let i = 0; i < html.length; i++) {
      if (html[i].nodeType === 1) {
        Drupal.attachBehaviors(html[i]);
      }
    }
  }

  /**
   * Inserts new messages into the thread.
   * @param {string} messages The new messages.
   */
  function insertNewMessages(messages) {
    // Render the messages as HTML, and set them to be hidden.
    const html = $('<div/>').html(messages).contents().css('display', 'none');

    // Insert the messages into the thread.
    if (drupalSettings.privateMessageThread.messageOrder === 'asc') {
      html.appendTo(container);
    } else {
      html.prependTo(container);
    }

    // Show the messages.
    html.slideDown(300);
    htmlAttachBehaviors(html);
  }

  // Insert older messages into the thread.
  function insertPreviousMessages(messages) {
    // Render the messages as HTML, setting them to be hidden.
    const html = $('<div/>').html(messages).contents().css('display', 'none');

    // Insert the messages into the thread.
    if (drupalSettings.privateMessageThread.messageOrder === 'asc') {
      html.prependTo(container);
    } else {
      html.appendTo(container);
    }

    // Show the messages.
    html.slideDown(300);
    htmlAttachBehaviors(html);
  }

  /**
   * Retrieves new messages from the server.
   */
  function getNewMessages() {
    // Only attempt a retrieval if one is not already in progress.
    if (!loadingNew) {
      let newestId = 0;

      loadingNew = true;

      // Get the thread ID.
      const threadId = $threadWrapper
        .children('.private-message-thread:first')
        .attr('data-thread-id');

      // Get the ID of the newest message. This will be used as a reference
      // server side to determine which messages to return to the browser.
      container.find('.private-message').each(() => {
        if (Number($(this).attr('data-message-id')) > newestId) {
          newestId = Number($(this).attr('data-message-id'));
        }
      });

      $.ajax({
        url: drupalSettings.privateMessageThread.newMessageCheckUrl,
        data: { threadid: threadId, messageid: newestId },
        success(data) {
          triggerCommands(data);

          loadingNew = false;

          if (refreshRate) {
            // Check for new messages again.
            timeout = window.setTimeout(getNewMessages, refreshRate);
          }
        },
      });
    }
  }

  /**
   * Remove the existing thread from the DOM, and insert a new one in its place.
   * @param {Object} thread The thread.
   */
  function insertThread(thread) {
    // Render the new thread as HTML.
    const $newThread = $('<div/>')
      .html(thread)
      .find('.private-message-thread:first');
    // Find the current thread in the DOM.
    const $originalThread = $threadWrapper.children(
      '.private-message-thread:first',
    );
    // Detach any behaviors from the old thread, to prevent memory leaks.
    Drupal.detachBehaviors($threadWrapper[0]);
    // Insert the new thread into the DOM.
    $newThread.insertAfter($originalThread);
    // Remove the old thread from teh DOM.
    $originalThread.remove();

    // Attach any behaviors to the new thread.
    Drupal.attachBehaviors($threadWrapper[0]);

    hideDimmer();
  }

  /**
   * Loads a thread from the server.
   * @param {string} threadId The thread ID.
   * @param {boolean} pushHistory True if the browser allows changing history.
   */
  function loadThread(threadId, pushHistory) {
    // Only try loading the thread if a thread isn't already loading, and if the
    // requested thread is not the current thread.
    if (!loadingThread && threadId !== currentThreadId) {
      loadingThread = true;

      window.clearTimeout(timeout);

      showDimmer();

      // Load the new thread from the server with AJAX.
      $.ajax({
        url: drupalSettings.privateMessageThread.loadThreadUrl,
        data: { id: threadId },
        success(data) {
          triggerCommands(data);

          if (Drupal.PrivateMessages.setActiveThread) {
            Drupal.PrivateMessages.setActiveThread(threadId);
          }

          loadingThread = false;

          timeout = window.setTimeout(getNewMessages, refreshRate);
        },
      });

      // The thread ID is changing. As such, we tell any other scripts that want
      // to know, that the thread has changed, and what the new thread ID is.
      Drupal.PrivateMessages.emitNewThreadId(threadId);

      // Change the URl if using a browser that allows it.
      if (pushHistory) {
        Drupal.history.push(
          { threadId },
          $('title').text(),
          `/private-messages/${threadId}`,
        );
      }
    }
  }

  /**
   * Insert button to load previous thread messages.
   */
  function insertPreviousButton() {
    const { messageTotal } = drupalSettings.privateMessageThread;
    const { messageCount } = drupalSettings.privateMessageThread;
    if (
      $threadWrapper.find('#load-previous-messages').length === 0 &&
      messageTotal > messageCount
    ) {
      // Initialize the previous button. This will be inserted into the thread.
      let loadPreviousButton;

      // Get the container for messages.
      container = $threadWrapper
        .find(
          '.private-message-thread-messages:first .private-message-wrapper:first',
        )
        .parent();

      // Don't add the load previous button if the thread is completely loaded.
      if (!container.hasClass('js-completely-loaded')) {
        // Create the HTML for the load previous button.
        loadPreviousButton = $('<div/>', {
          id: 'load-previous-messages-button-wrapper',
        }).append(
          $('<a/>', { href: '#', id: 'load-previous-messages' }).text(
            Drupal.t('Load Previous'),
          ),
        );

        // Insert the load previous button into the DOM.
        if (drupalSettings.privateMessageThread.messageOrder === 'asc') {
          loadPreviousButton
            .addClass('load-previous-position-before')
            .insertBefore(container);
        } else {
          loadPreviousButton
            .addClass('load-previous-position-after')
            .insertAfter(container);
        }
      }
    }
  }

  function init() {
    // Get the rate (in seconds) after which the server should be polled for
    // new messages.
    refreshRate = drupalSettings.privateMessageThread.refreshRate;
    // Find the wrapper for the current thread.
    $threadWrapper = $('.private-message-thread-full').parent();
    insertPreviousButton();

    if (!initialized) {
      initialized = true;

      // Get the original thread ID on page load.
      originalThreadId = $threadWrapper
        .children('.private-message-thread:first')
        .attr('data-thread-id');

      // If the refresh rate is anything above zero (zero is disabled) start the
      // server polling for new messages.
      if (refreshRate) {
        timeout = window.setTimeout(getNewMessages, refreshRate);
      }

      // Set the active thread.
      if (Drupal.PrivateMessages.setActiveThread) {
        Drupal.PrivateMessages.setActiveThread(originalThreadId);
      }
    }
  }

  Drupal.behaviors.privateMessageThread = {
    attach(context) {
      init();
      loadPreviousListener(context);
      currentThreadId = $threadWrapper
        .children('.private-message-thread:first')
        .attr('data-thread-id');
      container = $threadWrapper
        .find(
          '.private-message-thread-messages:first .private-message-wrapper:first',
        )
        .parent();

      // Ajax commands insertPrivateMessages command callback.
      Drupal.AjaxCommands.prototype.insertPrivateMessages = (
        ajax,
        response,
      ) => {
        if (response.insertType === 'new') {
          insertNewMessages(response.messages);
        } else {
          if (response.messages) {
            insertPreviousMessages(response.messages);
          }
          if (!response.hasNext) {
            container.addClass('js-completely-loaded');
            $('#load-previous-messages')
              .parent()
              .slideUp(300, () => {
                $(this).remove();
              });
          }
        }
      };

      // Ajax commands loadNewPrivateMessages command callback.
      Drupal.AjaxCommands.prototype.loadNewPrivateMessages = () => {
        window.clearTimeout(timeout);

        getNewMessages();
      };

      // Ajax commands privateMessageInsertThread command callback.
      Drupal.AjaxCommands.prototype.privateMessageInsertThread = (
        ajax,
        response,
      ) => {
        if (response.thread && response.thread.length) {
          insertThread(response.thread);
        }
      };

      // Lets other modules trigger the loading of a new thread into the page.
      Drupal.PrivateMessages.loadThread = (threadId) => {
        loadThread(threadId, true);
      };

      // Lets other modules trigger a retrieval of new messages from the server.
      Drupal.PrivateMessages.getNewMessages = () => {
        getNewMessages();
      };

      // Tells other modules the ID of a new thread that has been inserted into
      // the page.
      Drupal.PrivateMessages.emitNewThreadId = (threadId) => {
        $.each(Drupal.PrivateMessages.threadChange, (index) => {
          if (Drupal.PrivateMessages.threadChange[index].threadLoaded) {
            Drupal.PrivateMessages.threadChange[index].threadLoaded(threadId);
          }
        });
      };
    },
    detach: (context) => {
      $(context)
        .find('#load-previous-messages')
        .unbind('click', loadPreviousListenerHandler);
    },
  };

  // Integrates the script with the previous/next buttons in the browser.
  window.onpopstate = (e) => {
    if (e.state && e.state.threadId) {
      loadThread(e.state.threadId);
    } else {
      loadThread(originalThreadId);
    }
  };
})(jQuery, Drupal, drupalSettings, window, once);
