!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.submissionResults = {
    attach: function attach() {
      var yearFilter = new URLSearchParams(window.location.search);

      if (yearFilter.has('edit-type')) {
        var param = yearFilter.get('edit-type');
        var selectBox = $('#edit-type-');
        var matchedOption = selectBox.find('option[value="' + param + '"]');

        if (matchedOption.length) {
          matchedOption.prop('selected', true);
        }
      }

      var filterButton = $('.filter-btn');

      if (filterButton) {
        filterButton.each(function () {
          $(this).on('click', function () {
            var selectInput = $(this).parent().find('.form-select');
            var groupYearWrappers = $(this).parent().closest('.c-committees-container').find('.c-group-year');
            groupYearWrappers.each(function () {
              if (selectInput.val().toString() === 'All') {
                groupYearWrappers.css('display', 'block');
              } else {
                if (parseInt($(this).data('attributes-year')) !== parseInt(selectInput.val())) {
                  $(this).css('display', 'none');
                } else {
                  $(this).css('display', 'block');
                }
              }
            });
          });
        });
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=submission-results.js.map
