(function (Drupal) {
  Drupal.behaviors.hiddenLink = {
    attach: function (context, settings) {
      document.querySelectorAll('.c-container--hidden-link').forEach(function (container) {
        container.addEventListener('click', function () {
          let link = container.querySelector('a.js-link');
          if (link) {
            window.location.href = link.href;
          }
        });
      });
    }
  };
})(Drupal);
