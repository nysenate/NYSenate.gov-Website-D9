!function (document, Drupal) {
  'use strict';

  Drupal.behaviors.jumpnav = {
    attach: function attach() {
      var self = this;
      var navLinks = document.querySelectorAll('.jumpnav__link'); // There could be a better way to calculate this.

      var scrollOffset = 88;
      var sections = document.querySelectorAll('.content-form__main > details.form-wrapper');

      if (!navLinks.length) {
        return;
      } // Set first nav link to active on load.


      navLinks[0].classList.add('active');
      navLinks.forEach(function (item) {
        item.addEventListener('click', function (e) {
          e.preventDefault();
          self.removeActive(navLinks, sections);
          item.classList.add('active');
          var wrapper = document.querySelector(item.getAttribute('href'));
          wrapper.setAttribute('open', '');
          var count = wrapper.offsetTop - scrollOffset;
          window.scrollTo({
            top: count,
            left: 0,
            behavior: 'smooth'
          });
        });
      });
    },
    removeActive: function removeActive(menuItems, sections) {
      menuItems.forEach(function (item) {
        item.classList.remove('active');
      });
      sections.forEach(function (section) {
        section.removeAttribute('open');
      });
    }
  };
}(document, Drupal);
//# sourceMappingURL=jumpnav.js.map
