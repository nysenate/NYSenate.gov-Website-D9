!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.submissionResults = {
    attach: function () {
      let yearFilter = new URLSearchParams(window.location.search);
      if (yearFilter.has('edit-type')) {
        let param = yearFilter.get('edit-type');
        let selectBox = $('#edit-type-');
        let matchedOption = selectBox.find('option[value="' + param + '"]');
        if (matchedOption.length) {
          matchedOption.prop('selected', true);
        }
      }
      const filterButton = $('.filter-btn');
      if (filterButton) {
        filterButton.each(function () {
          $(this).on('click', function () {
            const selectInput = $(this).parent().find('.form-select');
            const groupYearWrappers = $(this)
              .parent()
              .closest('.c-committees-container')
              .find('.c-group-year');

            groupYearWrappers.each(function () {
              if (selectInput.val().toString() === 'All') {
                groupYearWrappers.css('display', 'block');
              }
              else {
                if (
                  parseInt($(this).data('attributes-year')) !==
                  parseInt(selectInput.val())
                ) {
                  $(this).css('display', 'none');
                }
                else {
                  $(this).css('display', 'block');
                }
              }
            });
          });
        });
      }
    }
  };
})(document, Drupal, jQuery);
