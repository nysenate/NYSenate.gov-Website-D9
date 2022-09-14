/**
 * @file
 * Behaviors for the Comments.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Comments behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.comments = {
    attach: function (context) {
      const commentsBlock = $('.comments', context);
      const replyBtn = $('.reply-btn');

      replyBtn.on(
        'click',
        function(event) {
          event.preventDefault();
          let target = event.target;
          let replyForm;

          if ($(target).data('toggle') === 'reply-form') {
            let formId = target.getAttribute('data-target');

            replyForm = commentsBlock.find(`#${formId}`);
            replyForm.toggleClass('hidden');
          }
        });
    }
  };
})(document, Drupal, jQuery);
