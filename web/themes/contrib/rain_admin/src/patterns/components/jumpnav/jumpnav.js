!((document, Drupal) => {
  'use strict';

  Drupal.behaviors.jumpnav = {
    attach() {
      const self = this;
      const navLinks = document.querySelectorAll('.jumpnav__link');
      // There could be a better way to calculate this.
      const scrollOffset = 88;
      const sections = document.querySelectorAll(
        '.content-form__main > details.form-wrapper'
      );

      if (!navLinks.length) {
        return;
      }

      // Set first nav link to active on load.
      navLinks[0].classList.add('active');

      navLinks.forEach((item) => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          self.removeActive(navLinks, sections);
          item.classList.add('active');
          const wrapper = document.querySelector(item.getAttribute('href'));
          wrapper.setAttribute('open', '');

          let count = wrapper.offsetTop - scrollOffset;
          window.scrollTo({top: count, left: 0, behavior: 'smooth'});
        });
      });
    },
    removeActive(menuItems, sections) {
      menuItems.forEach((item) => {
        item.classList.remove('active');
      });
      sections.forEach((section) => {
        section.removeAttribute('open');
      });
    }
  };
})(document, Drupal);
