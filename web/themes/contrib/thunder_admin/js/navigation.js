/**
 * @file
 * Handles responsive navigation blocks (breadcrumbs and tabs).
 */
(function ($, Drupal) {

  'use strict';

  function init(i, breadcrumb_block) {
    var $bcBlock = $(breadcrumb_block);
    var $tabsBlock = $bcBlock.siblings('.block-local-tasks-block');

    function handleResize() {
      $tabsBlock.addClass('is-combined-with-breadcrumb');

      var breadcrumbWidth = 0;
      $bcBlock.find('ol > li').each(function (index, elem) {
        breadcrumbWidth += $(elem).outerWidth(true);
      });

      var primaryTabsWidth = 0;
      $tabsBlock.find('.tabs.primary > li').each(function (index, elem) {
        primaryTabsWidth += $(elem).outerWidth(true);
      });

      $tabsBlock.toggleClass('is-combined-with-breadcrumb', $bcBlock.innerWidth() > (breadcrumbWidth + primaryTabsWidth));
    }

    $(window).on('resize.tabs', Drupal.debounce(handleResize, 50)).trigger('resize.tabs');

    // Register triggering of resize on menu expand.
    $('[data-toolbar-tray="toolbar-item-administration-tray"]')
      .once('responsive-navigation')
      .on('click', function () {
        $(window).trigger('resize.tabs');
      });
  }

  /**
   * Initialise the navigation JS.
   */
  Drupal.behaviors.navigation = {
    attach: function (context) {
      var $bcBlock = $(context).find('.block-system-breadcrumb-block');
      if ($bcBlock.length) {
        var notSmartPhone = window.matchMedia('(min-width: 300px)');
        if (notSmartPhone.matches) {
          $bcBlock.once('responsive-navigation').each(init);
        }
      }
    }
  };

})(jQuery, Drupal);
