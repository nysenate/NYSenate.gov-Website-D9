(function ($, window, Drupal, drupalSettings) {

  "use strict";

 // Scroll to given element
  Drupal.AjaxCommands.prototype.ajaxCommentsScrollToElement = function (ajax, response, status) {
    try {
      var pos = $(response.selector).offset();
      $('html, body').animate({ scrollTop: pos.top}, 'slow');
    }
    catch (e) {
      console.log('ajaxComments-ScrollToElementError: ' + e.name);
    }
  };

  /**
   * Add the dummy div if they are not exist.
   * On the server side we have a current state of node and comments, but on client side we may have a outdated state
   * and some div's may be not present
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsAddDummyDivAfter = function (ajax, response, status) {
    try {
      if (!$(response.selector).next().hasClass(response.class)) {
        $('<div class="' + response.class + '"></div>').insertAfter(response.selector);
      }
    }
    catch (e) {
      console.log('ajaxComments-AddDummyDivAfter: ' + e.name);
    }
  };

  /**
   * Override and extend the functionality of Drupal.Ajax.prototype.beforeSerialize.
   */
  (function (beforeSerialize) {
    Drupal.Ajax.prototype.beforeSerialize = function (element, options) {
      beforeSerialize.call(this, element, options);
      var wrapperHtmlId = $(element).data('wrapper-html-id') || null;
      if (wrapperHtmlId) {
        options.data['wrapper_html_id'] = wrapperHtmlId;
      }
    };
  })(Drupal.Ajax.prototype.beforeSerialize);

})(jQuery, this, Drupal, drupalSettings);
