/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Attaches the AJAX behavior to exposed filters forms and key View links.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches ajaxView functionality to relevant elements.
   */
  Drupal.behaviors.ViewsAjaxView = {};
  Drupal.behaviors.ViewsAjaxView.attach = function (context, settings) {
    if (settings && settings.views && settings.views.ajaxViews) {
      const {
        views: { ajaxViews },
      } = settings;
      Drupal.views.sortByNestingLevel(ajaxViews).forEach(({ key, value }) => {
        Drupal.views.instances[key] = new Drupal.views.ajaxView(value);
      });
    }
  };
  Drupal.behaviors.ViewsAjaxView.detach = (context, settings, trigger) => {
    if (trigger === 'unload') {
      if (settings && settings.views && settings.views.ajaxViews) {
        const {
          views: { ajaxViews },
        } = settings;
        Object.keys(ajaxViews || {}).forEach((i) => {
          const selector = `.js-view-dom-id-${ajaxViews[i].view_dom_id}`;
          if ($(selector, context).length) {
            delete Drupal.views.instances[i];
            delete settings.views.ajaxViews[i];
          }
        });
      }
    }
  };

  /**
   * @namespace
   */
  Drupal.views = {};

  /**
   * @type {object.<string, Drupal.views.ajaxView>}
   */
  Drupal.views.instances = {};

  /**
   * Sort the view Javascript objects by nesting level.
   *
   * @param {object} ajaxViews
   *   The views JavaScript object.
   * @param {string} settings.view_dom_id
   *   The DOM id of the view.
   * @param {string} settings.selector
   *   The selector of the view.
   *
   * @return {object}
   *   The sorted ajaxViews object.
   */
  Drupal.views.sortByNestingLevel = function (ajaxViews) {
    const ajaxViewsArray = Object.keys(ajaxViews || {}).map((key) => {
      ajaxViews[key].selector = `.js-view-dom-id-${ajaxViews[key].view_dom_id}`;

      return {
        key,
        value: ajaxViews[key],
        nestingLevel: $(ajaxViews[key].selector).parents('.view').length,
      };
    });

    return ajaxViewsArray.sort((a, b) => b.nestingLevel - a.nestingLevel);
  };

  /*
   * Javascript object for a certain view.
   * @constructor
   *
   * @param {object} settings
   *   Settings object for the ajax view.
   * @param {string} settings.view_dom_id
   *   The DOM id of the view.
   */
  Drupal.views.ajaxView = function (settings) {
    const { selector } = settings;
    this.$view = $(selector);

    // Retrieve the path to use for views' ajax.
    let ajaxPath = drupalSettings.views.ajax_path;

    // If there are multiple views this might've ended up showing up multiple
    // times.
    if (ajaxPath.constructor.toString().indexOf('Array') !== -1) {
      ajaxPath = ajaxPath[0];
    }

    // Check if there are any GET parameters to send to views.
    let queryString = window.location.search || '';
    if (queryString !== '') {
      // Remove the question mark and Drupal path component if any.
      queryString = queryString
        .slice(1)
        .replace(/q=[^&]+&?|&?render=[^&]+/, '');
      if (queryString !== '') {
        // If there is a '?' in ajaxPath, clean url are on and & should be
        // used to add parameters.
        queryString = (/\?/.test(ajaxPath) ? '&' : '?') + queryString;
      }
    }

    this.element_settings = {
      url: ajaxPath + queryString,
      submit: settings,
      setClick: true,
      event: 'click',
      selector,
      progress: { type: 'fullscreen' },
    };

    this.settings = settings;

    // Add the ajax to exposed forms.
    this.$exposed_form = $(
      `form#views-exposed-form-${settings.view_name.replace(
        /_/g,
        '-',
      )}-${settings.view_display_id.replace(/_/g, '-')}`,
    );
    once('exposed-form', this.$exposed_form).forEach(
      $.proxy(this.attachExposedFormAjax, this),
    );

    // Add the ajax to pagers.
    if (this.$view.find('.views-infinite-scroll-content-wrapper').length) {
      this.$pager_links = this.$view.find(
        'ul.js-pager__items > li > a, th.views-field a, .attachment .views-summary a',
      );
      once('ajax-pager', this.$pager_links).forEach(
        $.proxy(this.attachPagerLinkAjax, this),
      );
    } else {
      once(
        'ajax-pager',
        // Don't attach to nested views. Doing so would attach multiple
        // behaviors to a given element:
        this.$view.filter($.proxy(this.filterNestedViews, this)),
      ).forEach($.proxy(this.attachPagerAjax, this));
    }

    // Add a trigger to update this view specifically. In order to trigger a
    // refresh use the following code.
    //
    // @code
    // $('.view-name').trigger('RefreshView');
    // @endcode
    const selfSettings = $.extend({}, this.element_settings, {
      event: 'RefreshView',
      base: this.selector,
      element: this.$view.get(0),
    });
    // Remove unwanted parameter.
    delete selfSettings.selector;
    this.refreshViewAjax = Drupal.ajax(selfSettings);
  };

  /**
   * Attach the ajax behavior to exposed form fields.
   *
   * @param {HTMLElement} form
   *   The form element.
   */
  Drupal.views.ajaxView.prototype.attachExposedFormAjax = function (form) {
    if (!form) {
      form = this.$exposed_form;
    }
    this.exposedFormAjax = [];
    // Exclude the reset buttons so no AJAX behaviors are bound. Many things
    // break during the form reset phase if using AJAX.
    $('input[type=submit], button[type=submit], input[type=image]', form)
      .not(
        '[data-drupal-selector=edit-reset], [data-drupal-selector^="edit-tab-selector"]',
      )
      .each((index, element) => {
        const selfSettings = $.extend({}, this.element_settings, {
          base: $(element).attr('id'),
          element,
        });
        // Remove unwanted parameter.
        delete selfSettings.selector;
        this.exposedFormAjax[index] = Drupal.ajax(selfSettings);
      });
  };

  /**
   * @return {bool}
   *   If there is at least one parent with a view class return false.
   */
  Drupal.views.ajaxView.prototype.filterNestedViews = function () {
    // If there is at least one parent with a view class, this view
    // is nested (e.g., an attachment). Bail.
    return !this.$view.parents('.view').length;
  };

  /**
   * Attach the ajax behavior to each link.
   */
  Drupal.views.ajaxView.prototype.attachPagerAjax = function () {
    this.$view
      .find(
        'ul.js-pager__items > li > a, th.views-field a, .attachment .views-summary a',
      )
      .each($.proxy(this.attachPagerLinkAjax, this));
  };

  /**
   * Attach the ajax behavior to a singe link.
   *
   * @param {string} [id]
   *   The ID of the link.
   * @param {HTMLElement} link
   *   The link element.
   */
  Drupal.views.ajaxView.prototype.attachPagerLinkAjax = function (id, link) {
    const $link = $(link);
    const viewData = {};
    const href = $link.attr('href');
    // Construct an object using the settings defaults and then overriding
    // with data specific to the link.
    $.extend(
      viewData,
      this.settings,
      Drupal.Views.parseQueryString(href),
      // Extract argument data from the URL.
      Drupal.Views.parseViewArgs(href, this.settings.view_base_path),
    );

    const selfSettings = $.extend({}, this.element_settings, {
      submit: viewData,
      base: false,
      element: link,
    });
    // Remove unwanted parameter.
    delete selfSettings.selector;
    this.pagerAjax = Drupal.ajax(selfSettings);
  };

  /**
   * Views scroll to top ajax command.
   *
   * @param {Drupal.Ajax} [ajax]
   *   A {@link Drupal.ajax} object.
   * @param {object} response
   *   Ajax response.
   * @param {string} response.selector
   *   Selector to use.
   */
  Drupal.AjaxCommands.prototype.viewsScrollTop = function (ajax, response) {
    // Scroll to the top of the view. This will allow users
    // to browse newly loaded content after e.g. clicking a pager
    // link.
    const offset = $(response.selector).offset();
    // We can't guarantee that the scrollable object should be
    // the body, as the view could be embedded in something
    // more complex such as a modal popup. Recurse up the DOM
    // and scroll the first element that has a non-zero top.
    let scrollTarget = response.selector;
    while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
      scrollTarget = $(scrollTarget).parent();
    }
    // Only scroll upward.
    if (offset.top - 10 < $(scrollTarget).scrollTop()) {
      $(scrollTarget).animate({ scrollTop: offset.top - 10 }, 500);
    }
  };
})(jQuery, Drupal, drupalSettings);
