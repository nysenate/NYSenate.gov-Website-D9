!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.resolutionPage = {
    attach: function attach() {
      var quoteBillSocialToggle = $('.bill-sponsor-quote .js-quote-toggle');
      var quoteResSocialToggle = $('.c-block .js-quote-toggle');
      quoteBillSocialToggle.on('click', this.toggleBillSocial);
      quoteResSocialToggle.on('click', this.toggleResSocial);
    },
    toggleResSocial: function toggleResSocial() {
      var viewSocialClass = 'c-social-visible';
      var parent = $(this).parent('.c-block');

      if (parent.hasClass(viewSocialClass)) {
        parent.removeClass(viewSocialClass);
      } else {
        parent.addClass(viewSocialClass);
      }
    },
    toggleBillSocial: function toggleBillSocial() {
      var viewSocialClass = 'c-social-visible';
      var parent = $(this).parent('.bill-sponsor-quote"');

      if (parent.hasClass(viewSocialClass)) {
        parent.removeClass(viewSocialClass);
      } else {
        parent.addClass(viewSocialClass);
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=resolution-page.js.map
