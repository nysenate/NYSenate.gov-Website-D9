((Drupal) => {
  Drupal.behaviors.accessibleHiddenLinkStory = {
    attach: function (context, settings) {
      document.querySelectorAll('.c-container--story-hidden-link').forEach(function (container) {
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
