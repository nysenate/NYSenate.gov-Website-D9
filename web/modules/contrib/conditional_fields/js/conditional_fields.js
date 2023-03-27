(function ($, Drupal) {

/**
 * Enhancements to states.js.
 */
// Checking if autocomplete is plugged in.
if (Drupal.autocomplete) {
  /**
   * Handles an autocompleteselect event.
   *
   * Override the autocomplete method to add a custom event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {bool}
   *   Returns false to indicate the event status.
   */
  Drupal.autocomplete.options.select = function selectHandler(event, ui) {
    var terms = Drupal.autocomplete.splitValues(event.target.value);
    // Remove the current input.
    terms.pop();
    // Add the selected item.
    if (ui.item.value.search(',') > 0) {
      terms.push('"' + ui.item.value + '"');
    }
    else {
      terms.push(ui.item.value);
    }
    event.target.value = terms.join(', ');
    // Fire custom event that other controllers can listen to.
    jQuery(event.target).trigger('autocomplete-select');
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  };
}

/**
 * New and existing states enhanced with configurable options.
 * Event names of states with effects have the following structure:
 * state:stateName-effectName.
 */

//Visible/Invisible.
$(document).bind('state:visible-fade', function (e) {
  if (e.trigger) {
    $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'fadeIn' : 'fadeOut'](e.effect.speed);
  }
})
.bind('state:visible-slide', function (e) {
  if (e.trigger) {
    $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'slideDown' : 'slideUp'](e.effect.speed);
  }
})
// Empty/Filled.
.bind('state:empty', function (e) {
  if (e.trigger) {
    var fields = $(e.target).find('input, select, textarea');
    fields.each(function () {
      if (typeof $(this).data('conditionalFieldsSavedValue') === 'undefined') {
        $(this).data('conditionalFieldsSavedValue', $(this).val());
      }
      if (e.effect && e.effect.reset) {
        if (e.value) {
          $(this).val(e.effect.value);
        }
        else if ($(this).data('conditionalFieldsSavedValue')) {
          $(this).val($(this).data('conditionalFieldsSavedValue'));
        }
      }
    })
  }
})
// On invisible make empty and unrequired.
.bind('state:visible', function (e) {
  if (e.trigger) {
    // Save required property.
    if (typeof $(e.target).data('conditionalFieldsSavedRequired') === 'undefined') {
      var field = $(e.target).find('input, select, textarea');
      if (field) {
        $(e.target).data('conditionalFieldsSavedRequired', $(field).attr('required'));
      }
    }
    // Go invisible.
    if (!e.value) {
      // Remove required property.
      $(e.target).trigger({type: 'state:required', value: false, trigger: true});
    }
    // Go visible.
    else {
      // Restore required if necessary.
      if ($(e.target).data('conditionalFieldsSavedRequired')) {
        $(e.target).trigger({type: 'state:required', value: true, trigger: true});
      }
    }
  }
})
// Required/Not-Required.
.bind('state:required', function (e) {
    if (e.trigger) {
      var fields_supporting_required = $(e.target).find('input, textarea');
      var labels = $(e.target).find(':not(.form-item--editor-format, .form-type-radio)>label');
      if (e.value) {
        fields_supporting_required.filter(`[name *= "[0]"]`).attr('required', 'required');
        labels.addClass("form-required");
      } else {
        fields_supporting_required.removeAttr('required');
        labels.removeClass("form-required");
      }
    }
})
// Unchanged state. Do nothing.
.bind('state:unchanged', function () {});

Drupal.behaviors.conditionalFields = {
  attach: function (context, settings) {
    // AJAX is not updating settings.conditionalFields correctly.
    var conditionalFields = settings.conditionalFields || 'undefined';
    if (typeof conditionalFields === 'undefined' || typeof conditionalFields.effects === 'undefined') {
      return;
    }
    // Override state change handlers for dependents with special effects.
    var eventsData = $.hasOwnProperty('_data') ? $._data(document, 'events') : $(document).data('events');
    $.each(eventsData, function (i, events) {
      if (i.substring(0, 6) === 'state:') {
        var originalHandler = events[0].handler;
        events[0].handler = function (e) {
          var effect = conditionalFields.effects['#' + e.target.id];
          if (typeof effect !== 'undefined') {
            var effectEvent = i + '-' + effect.effect;
            if (typeof eventsData[effectEvent] !== 'undefined') {
              $(e.target).trigger({ type : effectEvent, trigger : e.trigger, value : e.value, effect : effect.options });
              return;
            }
          }
          originalHandler(e);
        }
      }
    });
  }
};

Drupal.behaviors.ckeditorTextareaFix = {
    attach: function (context, settings) {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.on('instanceReady', function () {
                $(context).find('.form-textarea-wrapper textarea').each(function () {
                    var $textarea = jQuery(this);
                    if (CKEDITOR.instances[$textarea.attr('id')] != undefined) {
                        CKEDITOR.instances[$textarea.attr('id')].on('change', function () {
                            CKEDITOR.instances[$textarea.attr('id')].updateElement();
                            $textarea.trigger('keyup');
                        });
                    }
                });
            });
        }
    }
};

Drupal.behaviors.autocompleteChooseTrigger = {
    attach: function (context, settings) {
        $(context).find('.form-autocomplete').each(function () {
            var $input = $(this);
            $(this).on('autocomplete-select', function (event, node) {
                setTimeout(function () {
                    $input.trigger("keyup");
                }, 1);
            });
        });
    }
};

Drupal.behaviors.statesModification = {
  weight: -10,
  attach: function (context, settings) {
    if (Drupal.states) {
      /**
       * Handle array values.
       * @see http://drupal.org/node/1149078
       */
      Drupal.states.Dependent.comparisons['Array'] = function (reference, value) {
        // Make sure value is an array.
        var compare = [];
        if ( typeof value === "string" ) {
          compare = value.split(/\r?\n\r?/);
        } else if ( typeof(value) === "object" && value instanceof Array ) {
          compare = value;
        }

        if (compare.length < 1 ) {
          return false;
        }
        // We iterate through each value provided in the reference. If all of them
        // exist in value array, we return true. Otherwise return false.
        for (var key in reference) {
          if (reference.hasOwnProperty(key) && $.inArray(String(reference[key]), compare) < 0) {
            return false;
          }
        }
        return true;
      };

      /**
       * Handle object values.
       */
      Drupal.states.Dependent.comparisons.Object = function (reference, value) {

        /**
         * Adds RegEx support
         * https://www.drupal.org/node/1340616
         */
        if ('regex' in reference) {
          //The fix for regex when value is array
          var regObj = new RegExp(reference.regex, reference.flags);
          if ( value && value.constructor.name == 'Array' ) {
           for (var index in value) {
            if (regObj.test( value[index])) {
              return true;
            }
           }
           return  false;
          } else {
            return regObj.test(value);
          }
          //Adds single XOR support
        }else if ('xor' in reference) {
          var compare = [];
          if ( typeof value === "string" ) {
            compare = value.split(/\r?\n\r?/);
          } else if ( typeof(value) === "object" && value instanceof Array ) {
            compare = value;
          }
          var eq_count = 0;
          for (var key in reference.xor) {
            if (reference.xor.hasOwnProperty(key) && $.inArray( reference.xor[key], compare) >= 0) {
              eq_count++;
            }
          }
          return eq_count % 2 == 1;
        }
        else {
          return reference.indexOf(value) !== false;
        }
      }
      //The fix for compare strings wrapped by control symbols
      Drupal.states.Dependent.comparisons.String = function ( reference, value ) {
        if ( value && value.constructor.name == 'Array' ) {
         for (var index in value) {
           if (_compare2(reference, value[index])) {
             return true;
           }
         }
         return false;
        } else {
          return _compare2(reference, value);
        }
      }
    }
  }
};

  /**
   * The function for compare two strings
   * @param a
   * @param b
   * @returns {boolean|*}
   * @private
   */
  function _compare2(a, b) {
    a = typeof a == "string" ? a.replace(/(^[\n\r]+|[\n\r]+$)/g, '') : a;
    b = typeof b == "string" ? b.replace(/(^[\n\r]+|[\n\r]+$)/g, '') : b;
    if (a === b) {
      return typeof a === 'undefined' ? a : true;
    }

    return typeof a === 'undefined' || typeof b === 'undefined';
  }
})(jQuery, Drupal);
