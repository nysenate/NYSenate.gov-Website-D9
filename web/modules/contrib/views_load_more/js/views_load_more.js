/**
 * @file
 *
 * Handles the AJAX pager for the view_load_more plugin.
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Provide a series of commands that the server can request the client perform.
   */
  Drupal.AjaxCommands.prototype.viewsLoadMoreAppend = function (ajax, response) {
    // Configured options for the pager
    var options = response.options,
      // Get information from the response. If it is not there, default to
      // our presets.
      method = options.method,
      wrapper_selector = options.wrapper_selector,
      $wrapper = $(wrapper_selector),
      content_selector = options.content_selector,
      pager_selector = options.pager_selector,
      effect = options.effect,
      speed = options.speed,
      target_list = options.target_list,
      settings = response.settings || ajax.settings || drupalSettings,
      // We don't know what response.data contains: it might be a string of text
      // without HTML, so don't rely on jQuery correctly iterpreting
      // $(response.data) as new HTML rather than a CSS selector. Also, if
      // response.data contains top-level text nodes, they get lost with either
      // $(response.data) or $('<div></div>').replaceWith(response.data).
      new_content_wrapped = $('<div></div>').html($.trim(response.data)),
      new_content = new_content_wrapped.contents();

    // For legacy reasons, the effects processing code assumes that new_content
    // consists of a single top-level element. Also, it has not been
    // sufficiently tested whether attachBehaviors() can be successfully called
    // with a context object that includes top-level text nodes. However, to
    // give developers full control of the HTML appearing in the page, and to
    // enable Ajax content to be inserted in places where DIV elements are not
    // allowed (e.g., within TABLE, TR, and SPAN parents), we check if the new
    // content satisfies the requirement of a single top-level element, and
    // only use the container DIV created above when it doesn't. For more
    // information, please see http://drupal.org/node/736066.
    if (new_content.length != 1 || new_content.get(0).nodeType != 1) {
      new_content = new_content_wrapped;
    }

    // If removing content from the wrapper, detach behaviors first.
    Drupal.detachBehaviors($wrapper[0], settings);

    // Set up our default query options. This is for advance users that might
    // change there views layout classes. This allows them to write there own
    // jquery selector to replace the content with.
    // Provide sensible defaults for unordered list, ordered list and table
    // view styles.
    if (target_list) {
      content_selector += ' ' + target_list;
    }

    // If we're using any effects. Hide the new content before adding it to the DOM.
    if (effect) {
      new_content.find(content_selector).children().hide();
    }

    // Update the pager
    // Find both for the wrapper as the newly loaded content the direct child
    // .item-list in case of nested pagers
    $wrapper.find(pager_selector).replaceWith(new_content.find(pager_selector));

    // Add the new content to the page.
    $wrapper.find(content_selector)[method](new_content.find(content_selector).children());

    // Use the effect to show content if defined.
    if (effect) {
      $wrapper.find(content_selector).children(':not(:visible)')[effect](speed);
    }

    // Additional processing over new content
    $wrapper.trigger('views_load_more.new_content', new_content.clone());

    // Attach all JavaScript behaviors to the new content
    $wrapper.removeOnce('ajax-pager');
    Drupal.attachBehaviors($wrapper[0], settings);
  };

})(jQuery, Drupal, drupalSettings);
