/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysenateTwitterBlock = {
    attach: function attach() {
      //Add twitter widget styling
      var $iframeHead;
      var twitterStylesTimer = window.setInterval(function () {
        $iframeHead = $('iframe#twitter-widget-0').contents().find('head');

        if (!$('#twitter-widget-styles', $iframeHead).length) {
          //If our stylesheet does not exist tey to place it
          $iframeHead.append('<link rel="stylesheet" href="/themes/custom/nysenate_theme/dist/css/global.css" id="twitter-widget-styles">');
          $iframeHead.append('<link rel="stylesheet" href="/themes/custom/nysenate_theme/dist/css/nysenate-twitter-block.css" id="twitter-widget-styles">');
        } else if ($('#twitter-widget-styles', $iframeHead).length) {
          //If stylesheet exists then quit timer
          clearInterval(twitterStylesTimer);
        }
      }, 200);
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-twitter-block.js.map
