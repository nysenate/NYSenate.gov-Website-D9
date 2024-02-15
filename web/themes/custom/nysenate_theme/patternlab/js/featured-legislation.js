!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.featuredLegislation = {
    attach: function attach() {
      var featLegToggle = $('.c-block-legislation-featured .js-leg-toggle');
      featLegToggle.on('click', this.toggleFeatureLeg);
    },
    toggleFeatureLeg: function toggleFeatureLeg() {
      var collapseClass = 'c-block__collapsed';
      var viewSocialClass = 'c-social-visible';
      var parent = $(this).parent('.c-block-legislation-featured');

      if (parent.hasClass(collapseClass)) {
        parent.removeClass(collapseClass);
      } else {
        if (parent.hasClass(viewSocialClass)) {
          parent.removeClass(viewSocialClass);
        } else {
          parent.addClass(viewSocialClass);
        }
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=featured-legislation.js.map
