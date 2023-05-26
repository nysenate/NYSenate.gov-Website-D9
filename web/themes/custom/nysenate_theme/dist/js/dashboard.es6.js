/**
 * @file
 * Behaviors for the Dashboard Header.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Dashboard Header behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.dashboard = {
    attach: function attach(context) {
      if (context !== document) {
        return;
      }

      var win = $(window);
      var origNav = $('#js-sticky--dashboard', context);
      var self = this;
      var debounceTime = 250;
      origNav.once('navigation').each(function () {
        var nav = origNav.clone().attr('id', 'js-sticky--dashboard--clone');
        nav.addClass('fixed');
        var headerBar = nav.find('.c-header-bar');
        var sidebarToggle = nav.find('.sidebar-toggle');
        sidebarToggle.each(Drupal.behaviors.sidebar.sidebarToggleInit); // place clone

        nav.prependTo('.page').css({
          'z-index': '100'
        });
        var navTop = nav.offset().top;
        self.alignPosition(origNav, nav, navTop);
        win.scroll(Drupal.debounce(function () {
          return self.checkTopBarState(nav, headerBar);
        }, 300));
        win.scroll(Drupal.debounce(function () {
          return self.alignPosition(origNav, nav, navTop);
        }, debounceTime));
        win.resize(Drupal.debounce(function () {
          return self.alignPosition(origNav, nav, navTop);
        }, debounceTime));
        self.initToolbarObserver(origNav, nav, self.alignPosition);
      });
    },
    checkTopBarState: function checkTopBarState(nav, headerBar) {
      var doc = $(document);
      var currentTop = doc.scrollTop();

      if (currentTop > nav.outerHeight() && !headerBar.hasClass('collapsed')) {
        headerBar.addClass('collapsed');
      } else if (currentTop <= nav.outerHeight() && headerBar.hasClass('collapsed')) {
        headerBar.removeClass('collapsed');
      }

      setTimeout(function () {
        return Drupal.behaviors.sidebar.onResize(nav);
      }, 300);
    },
    alignPosition: function alignPosition(orig, clone, cloneTop) {
      try {
        var win = $(window);
        var isMobile = win.width() < 576;
        var winScrollTop = win.scrollTop();

        if (isMobile) {
          clone.removeAttr('style');
          orig.addClass('fixed');

          if (winScrollTop > cloneTop) {
            clone.addClass('fixed');
            orig.removeClass('fixed');
          } else {
            clone.removeClass('fixed');
            orig.addClass('fixed');
          }
        } else {
          var origTop = orig.position().top;
          clone.css('top', "".concat(typeof origTop === 'number' ? origTop : 0, "px"));
          clone.addClass('fixed');
          orig.removeClass('fixed');
          orig.removeAttr('style');
        }

        setTimeout(function () {
          return Drupal.behaviors.dashboard.alignFixedTableHeader(clone);
        }, 300);
      } catch (err) {
        return err;
      }
    },
    alignFixedTableHeader: function alignFixedTableHeader(clone) {
      try {
        var pageUserTableTh = $('.sticky-header');

        if (!pageUserTableTh) {
          return;
        }

        var cloneTop = clone.position().top + clone.height();
        pageUserTableTh.css('top', "".concat(typeof cloneTop === 'number' ? cloneTop : 0, "px"));
      } catch (err) {
        return err;
      }
    },
    initToolbarObserver: function initToolbarObserver(origNav, nav, alignPosition) {
      // Select the node that will be observed for mutations
      var targetNode = $('body'); // Options for the observer (which mutations to observe)

      var config = {
        attributes: true,
        childList: true,
        subtree: true
      }; // Callback function to execute when mutations are observed

      var callback = function callback(mutationList) {
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = mutationList[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var mutation = _step.value;

            if (mutation.attributeName === 'style' && mutation.target.localName === 'body') {
              alignPosition(origNav, nav);
            }
          }
        } catch (err) {
          _didIteratorError = true;
          _iteratorError = err;
        } finally {
          try {
            if (!_iteratorNormalCompletion && _iterator.return != null) {
              _iterator.return();
            }
          } finally {
            if (_didIteratorError) {
              throw _iteratorError;
            }
          }
        }
      }; // Create an observer instance linked to the callback function


      var observer = new MutationObserver(callback);

      try {
        // Start observing the target node for configured mutations
        targetNode.each(function () {
          observer.observe(this, config);
        });
      } catch (err) {
        observer.disconnect();
        return err;
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=dashboard.es6.js.map
