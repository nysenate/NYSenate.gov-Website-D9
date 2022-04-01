/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.articleCardCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.article-card__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.article-card__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.article-card__arrow.arrow-left',
        nextArrow: '.article-card__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=article-card.es6.js.map

/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.blogCardCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.blog-card__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.blog-card__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.blog-card__arrow.arrow-left',
        nextArrow: '.blog-card__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=blog-card.es6.js.map

/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.cardListCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.card-list__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.card-list__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.card-list__arrow.arrow-left',
        nextArrow: '.card-list__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=card-list.es6.js.map

/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.eventCardCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.event-card__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.event-card__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.event-card__arrow.arrow-left',
        nextArrow: '.event-card__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=event-card.es6.js.map

/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.galleryCarousel = {
    attach: function attach() {
      $('.gallery-carousel').not('.slick-initialized').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.gallery-carousel__nav',
        infinite: false
      });
      $('.gallery-carousel__nav').not('.slick-initialized').slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        asNavFor: '.gallery-carousel',
        focusOnSelect: true,
        arrows: false,
        infinite: false,
        responsive: [{
          /* xsm breakpoint equivalent */
          breakpoint: 640,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 3,
            infinite: false
          }
        }]
      });
      $('.arrow-left').click(function () {
        $('.gallery-carousel').slick('slickPrev');
      });
      $('.arrow-right').click(function () {
        $('.gallery-carousel').slick('slickNext');
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=gallery-carousel.es6.js.map

!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.mainMenu = {
    attach: function attach(context) {
      var $navicon = $('.main-menu__navicon', context);
      var $menuItemToggle = $('.menu-item-toggle', context);
      var $searchToggle = $('.search__toggle', context);
      $(document).keyup(function (e) {
        // `27` is the code for the escape key.
        if (e.which === 27) {
          if ($navicon.hasClass('active')) {
            $navicon.trigger('click');
          }

          $menuItemToggle.each(function () {
            var $this = $(this);

            if ($this.parent().hasClass('js-open')) {
              $this.trigger('click');
            }
          });
        }
      });
      $navicon.click(function () {
        $(this).toggleClass('active');
        $('header').toggleClass('open-menu');
        $('.main-menu').toggleClass('active');
        $('.user-account-nav').toggleClass('account-nav-active');

        if (!$('header').hasClass('open-menu')) {
          $('.search-api-page-block-form-search-results').removeClass('active');
          $('header').removeClass('open-search');
        }
      });
      $searchToggle.click(function () {
        $('header').toggleClass('open-search');
        $('.search-api-page-block-form-search-results').toggleClass('active');
      });
      $('li.menu-item--has-submenu', context).hover(function () {
        // Mouse enter.
        if ($('.main-menu__navicon', context).is(':hidden')) {
          $(this).addClass('js-open').children('a').attr('aria-expanded', 'true');
        }
      }, function () {
        if ($('.main-menu__navicon', context).is(':hidden')) {
          // Mouse leave.
          $(this).removeClass('js-open').children('a').attr('aria-expanded', 'false');
        }
      });
      $menuItemToggle.click(function () {
        var $this = $(this);

        if ($this.parent().hasClass('js-open')) {
          $this.parent().removeClass('js-open');
          $this.siblings('a').attr('aria-expanded', 'false');
        } else {
          $this.parent().addClass('js-open');
          $this.siblings('a').attr('aria-expanded', 'true');
        }
      });
      $(window).resize(function () {
        $('li.menu-item--has-submenu').removeClass('js-open');
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=main-menu.js.map

/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.productCardCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.product-card__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.product-card__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.product-card__arrow.arrow-left',
        nextArrow: '.product-card__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=product-card.es6.js.map
