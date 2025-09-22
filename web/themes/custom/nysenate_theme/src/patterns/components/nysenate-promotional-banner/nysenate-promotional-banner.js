((Drupal) => {
  Drupal.behaviors.accessibleHiddenLinkInitiative = {
    attach: function (context, settings) {
      document.querySelectorAll('.c-container--initiative-hidden-link').forEach(function (container) {
        container.addEventListener('click', function () {
          let link = container.querySelector('a'); // Get the title link
          if (link) {
            window.location.href = link.href;
          }
        });
      });
    }
  };
})(Drupal);
