!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.resolutionPage = {
    attach: function () {
      const quoteBillSocialToggle = $('.bill-sponsor-quote .js-quote-toggle');
      const quoteResSocialToggle = $('.c-block .js-quote-toggle');
      quoteBillSocialToggle.on('click', this.toggleBillSocial);
      quoteResSocialToggle.on('click', this.toggleResSocial);
    },
    toggleResSocial: function() {
      var viewSocialClass = 'c-social-visible';
      var parent = $(this).parent('.c-block');

      if(parent.hasClass(viewSocialClass)) {
        parent.removeClass(viewSocialClass);
      }
      else {
        parent.addClass(viewSocialClass);
      }
    },
    toggleBillSocial: function() {
      var viewSocialClass = 'c-social-visible';
      var parent = $(this).parent('.bill-sponsor-quote"');

      if(parent.hasClass(viewSocialClass)) {
        parent.removeClass(viewSocialClass);
      }
      else {
        parent.addClass(viewSocialClass);
      }
    },

  };
})(document, Drupal, jQuery);
