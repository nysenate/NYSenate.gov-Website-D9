/**
 * @file
 * Behaviors for the Comments.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Comments behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.comments = {
    attach: function attach(context) {
      var commentsBlock = $('.comments', context);
      var replyBtn = $('.reply-btn');
      replyBtn.on('click', function (event) {
        event.preventDefault();
        var target = event.target;
        var replyForm;

        if ($(target).data('toggle') === 'reply-form') {
          var formId = target.getAttribute('data-target');
          replyForm = commentsBlock.find("#".concat(formId));
          replyForm.toggleClass('hidden');
        }
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=comments.es6.js.map
