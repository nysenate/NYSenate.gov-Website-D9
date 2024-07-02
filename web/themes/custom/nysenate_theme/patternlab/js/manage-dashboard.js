/**
 * @file
 *
 * Behaviors for the Manage Dashboard form.
 */
(function (Drupal, once) {
  Drupal.behaviors.nysManageDashboard = {
    attach: function attach() {
      // Disable submit button by default
      var submitButton = document.querySelector('#nys-dashboard-manage-dashboard #edit-submit');
      submitButton.disabled = true; // Enable submit button if any input unchecked.

      var fields = document.querySelectorAll('#nys-dashboard-manage-dashboard input.form-checkbox');
      fields = Array.from(fields);
      fields.forEach(function (field) {
        field.addEventListener('change', function () {
          submitButton.disabled = fields.every(function (field) {
            return field.checked;
          }); //field.checked;
        });
      }); // Provides "uncheck all" functionality.

      var uncheckAllButtons = document.getElementsByClassName('uncheck-all-button');
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        var _loop = function _loop() {
          var uncheckAllButton = _step.value;

          uncheckAllButton.onclick = function () {
            var checkboxes = uncheckAllButton.closest('.form-checkboxes').getElementsByClassName('form-checkbox');
            var _iteratorNormalCompletion2 = true;
            var _didIteratorError2 = false;
            var _iteratorError2 = undefined;

            try {
              for (var _iterator2 = checkboxes[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
                var checkbox = _step2.value;
                checkbox.checked = false;
                submitButton.disabled = false;
              }
            } catch (err) {
              _didIteratorError2 = true;
              _iteratorError2 = err;
            } finally {
              try {
                if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
                  _iterator2.return();
                }
              } finally {
                if (_didIteratorError2) {
                  throw _iteratorError2;
                }
              }
            }

            return false;
          };
        };

        for (var _iterator = uncheckAllButtons[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          _loop();
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
    }
  };
})(Drupal, once);
//# sourceMappingURL=manage-dashboard.js.map
