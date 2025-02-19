!((document, Drupal, $) => {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.c-container--hidden-link').forEach(function (container) {
      container.addEventListener('click', function () {
        let link = container.querySelector('a'); // Get the title link
        if (link) {
          window.location.href = link.href;
        }
      });
    });
  });
})(document, Drupal, jQuery);
