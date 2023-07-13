/**
 * @file
 * Linkit Autocomplete based on jQuery UI.
 */

(function ($, Drupal, once) {

  'use strict';

  var autocomplete;

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    var elementId = this.element.attr('id');

    if (!(elementId in autocomplete.cache)) {
      autocomplete.cache[elementId] = {};
    }

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      autocomplete.cache[elementId][term] = data.suggestions;
      response(data.suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.
    var term = request.term;

    // Check if the term is already cached.
    if (autocomplete.cache[elementId].hasOwnProperty(term)) {
      response(autocomplete.cache[elementId][term]);
    }
    else {
      var options = $.extend({
        success: sourceCallbackHandler,
        data: {q: term}
      }, autocomplete.ajax);
      $.ajax(this.element.attr('data-autocomplete-path'), options);
    }
  }

  /**
   * Handles an autocomplete select event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {boolean}
   *   False to prevent further handlers.
   */
  function selectHandler(event, ui) {
    var linkSelector = event.target.getAttribute('data-drupal-selector');
    var $context = $(event.target).closest('form,fieldset,tr');

    if (!ui.item.path) {
      throw 'Missing path param.' + JSON.stringify(ui.item);
    }

    $('input[name="href_dirty_check"]', $context).val(ui.item.path);

    if (ui.item.entity_type_id || ui.item.entity_uuid || ui.item.substitution_id) {
      if (!ui.item.entity_type_id || !ui.item.entity_uuid || !ui.item.substitution_id) {
        throw 'Missing path param.' + JSON.stringify(ui.item);
      }
    }
    $('input[name="attributes[href]"], input[name$="[attributes][href]"]', $context).val(ui.item.path);
    $('input[name="attributes[data-entity-type]"], input[name$="[attributes][data-entity-type]"]', $context).val(ui.item.entity_type_id);
    $('input[name="attributes[data-entity-uuid]"], input[name$="[attributes][data-entity-uuid]"]', $context).val(ui.item.entity_uuid);
    $('input[name="attributes[data-entity-substitution]"], input[name$="[attributes][data-entity-substitution]"]', $context).val(ui.item.substitution_id);

    if (ui.item.label) {
      // Automatically set the link title.
      var $linkTitle = $('*[data-linkit-widget-title-autofill-enabled]', $context);
      if ($linkTitle.length > 0) {
        var titleSelector = $linkTitle.attr('data-drupal-selector');
        if (titleSelector === undefined || linkSelector === undefined) {
          return false;
        }
        if (titleSelector.replace('-title', '') !== linkSelector.replace('-uri', '')) {
          return false;
        }
        if (!$linkTitle.val() || $linkTitle.hasClass('link-widget-title--auto')) {
          // Set value to the label.
          $linkTitle.val(ui.item.label);
          // Flag title as being automatically set.
          $linkTitle.addClass('link-widget-title--auto');
        }
      }
    }

    event.target.value = ui.item.path;

    return false;
  }

  /**
   * Override jQuery UI _renderItem function to output HTML by default.
   *
   * @param {object} ul
   *   The <ul> element that the newly created <li> element must be appended to.
   * @param {object} item
   *  The list item to append.
   *
   * @return {object}
   *   jQuery collection of the ul element.
   */
  function renderItem(ul, item) {
    var $line = $('<li>').addClass('linkit-result-line');
    var $wrapper = $('<div>').addClass('linkit-result-line-wrapper');
    $wrapper.append($('<span>').html(item.label).addClass('linkit-result-line--title'));

    if (item.hasOwnProperty('description')) {
      $wrapper.append($('<span>').html(item.description).addClass('linkit-result-line--description'));
    }
    return $line.append($wrapper).appendTo(ul);
  }

  /**
   * Override jQuery UI _renderMenu function to handle groups.
   *
   * @param {object} ul
   *   An empty <ul> element to use as the widget's menu.
   * @param {array} items
   *   An Array of items that match the user typed term.
   */
  function renderMenu(ul, items) {
    var self = this.element.autocomplete('instance');

    var grouped_items = {};
    items.forEach(function (item) {
      const group = item.hasOwnProperty('group') ? item.group : '';
      if (!grouped_items.hasOwnProperty(group)) {
        grouped_items[group] = [];
      }
      grouped_items[group].push(item);
    })

    $.each(grouped_items, function (group, items) {
      if (group.length) {
        ul.append('<li class="linkit-result-line--group ui-menu-divider">' + group + '</li>');
      }

      $.each(items, function (index, item) {
        if ( $.isFunction(self._renderItemData) ) {
          self._renderItemData(ul, item);
        }
      });
    });
  }

  function focusHandler() {
    return false;
  }

  function searchHandler(event) {
    var options = autocomplete.options;

    return !options.isComposing;
  }

  /**
   * Attaches the autocomplete behavior to all required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the autocomplete behaviors.
   */
  Drupal.behaviors.linkit_autocomplete = {
    attach: function (context) {
      // Act on textfields with the "form-linkit-autocomplete" class.
      var $autocomplete = $(once('linkit-autocomplete', 'input.form-linkit-autocomplete', context));
      if ($autocomplete.length) {
        $.widget('ui.autocomplete', $.ui.autocomplete, {
          _create: function () {
            this._super();
            this.widget().menu('option', 'items', '> :not(.linkit-result-line--group)');
          },
          _renderMenu: autocomplete.options.renderMenu,
          _renderItem: autocomplete.options.renderItem
        });

        // Process each item.
        $autocomplete.each(function () {
          var $uri = $(this);

          // Use jQuery UI Autocomplete on the textfield.
          $uri.autocomplete(autocomplete.options);
          $uri.autocomplete('widget').addClass('linkit-ui-autocomplete');

          $uri.click(function () {
            $uri.autocomplete('search', $uri.val());
          });

          $uri.on('compositionstart.autocomplete', function () {
            autocomplete.options.isComposing = true;
          });
          $uri.on('compositionend.autocomplete', function () {
            autocomplete.options.isComposing = false;
          });

          $uri.closest('.form-item').siblings('.form-type-textfield').find('.linkit-widget-title')
            .each(function() {
              // Set automatic title flag if title is the same as uri text.
              var $title  = $(this);
              var uriValue = $uri.val();
              if (uriValue && uriValue === $title.val()) {
                $title.addClass('link-widget-title--auto');
              }
            })
            .change(function () {
              // Remove automatic title flag.
              $(this).removeClass('link-widget-title--auto');
            });
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('linkit-autocomplete', 'input.form-linkit-autocomplete', context)
          .forEach((autocomplete) => $(autocomplete).autocomplete('destroy'));
      }
    }
  };

  /**
   * Autocomplete object implementation.
   */
  autocomplete = {
    cache: {},
    options: {
      source: sourceData,
      focus: focusHandler,
      search: searchHandler,
      select: selectHandler,
      renderItem: renderItem,
      renderMenu: renderMenu,
      minLength: 1,
      isComposing: false
    },
    ajax: {
      dataType: 'json'
    }
  };

})(jQuery, Drupal, once);
