/**
 * @file
 * Behaviors for the Hero.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';
  /**
   * Setup and attach the Hero behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dashboardInbox = {
    attach: function (context) {
      // eslint-disable-next-line no-console
      console.log('hello');

      const toggleBtn = $('.message-body-toggle', context);
      toggleBtn.each(function () {
        const actionBtns = $(this).parent().find('.message-action-buttons');
        $(this).click(function () {

          if (actionBtns.css('display') === 'flex') {
            actionBtns.css('display', 'none');
            $(this).removeClass('message-body-toggle--expanded');
          }
          else {
            actionBtns.css('display', 'flex');
            $(this).addClass('message-body-toggle--expanded');
          }
        });
      });

      // listIssue.each(function () {
      //   console.log('hello');
      // });

      // $(context).once('.privatemsg-list-issue').on('click');

      // once(
      //   'initPrivateMsgListIssue',
      //   '.privatemsg-list-issue',
      //   context
      // ).forEach(function (element) {
      //   const toggleBtn = $(element).find('.message-body-toggle');

      //   toggleBtn.click(function (el) {
      //     $(el).css('background', 'red');
      //     // alert('clicked!');
      //     // console.log('clicked');
      //   });
      //   // $(element).
      // });

      // once(
      //   'initPrivateMsgListIssue',
      //   '.privatemsg-list-issue',
      //   context
      // ).forEach(function (element) {
      //   const toggleBtn = $(element).find('.message-body-toggle');

      //   toggleBtn.click(function (el) {
      //     $(el).css('background', 'red');
      //     // alert('clicked!');
      //     // console.log('clicked');
      //   });
      //   // $(element).
      // });

      //   const self = this;
      //   const heroContainer = $('.hero--homepage');
      //   let pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
      //   let pagePadding = ($('main').innerWidth() - $('main').width()) / 2;

      //   self.heroMargin(heroContainer, pageMargin, pagePadding);

      //   $(window).on('resize', function () {
      //     pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
      //     pagePadding = ($('main').innerWidth() - $('main').width()) / 2;

      //     self.heroMargin(heroContainer, pageMargin, pagePadding);
      //   });
      // },
      // heroMargin: function (heroContainer, pageMargin, pagePadding) {
      //   if ($(window).width() >= 1500) {
      //     heroContainer.css('margin-left', `-${1500 / 4 }px`);
      //   }
      //   else {
      //     heroContainer.css('margin-left', `-${pageMargin + pagePadding}px`);
      //   }
    }
  };

  // eslint-disable-next-line no-undef
})(document, Drupal, jQuery);
