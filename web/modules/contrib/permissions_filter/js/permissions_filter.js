/**
 * @file
 * Permission page behaviors.
 */

(function (Drupal, debounce) {

    'use strict';
    Drupal.behaviors.permissionTableFilterByText = {
        attach: function (context, settings) {
            var $input = once('table-filter-text', 'input.table-filter-text')[0];
            var $table = document.querySelector($input.getAttribute('data-table'));
            var $rowsAndDetails;
            var $rows;
            var $details;
            var searching = false;

            function hidePackageDetails(element) {
                [...element.querySelectorAll('tbody tr')]
                    .forEach((row) => {
                        row.style.display = 'none'
                    });
            }

            function filterPermissionList(e) {
                var query = e.target.value;
                // Case insensitive expression to find query at the beginning of a word.
                var re = new RegExp('\\b' + query, 'i');

                function showPermissionRow(row) {
                    var source = row.querySelector('.table-filter-text-source, .module, .permission');
                    var textMatch = source.textContent.search(re) !== -1;
                    row.closest('tr').style.display = textMatch ? '' : 'none';
                }

                // Search over all rows and packages.
                [...$rowsAndDetails].forEach((row) => {
                    row.style.display = ''
                });

                // Filter if the length of the query is at least 2 characters.
                if (query.length >= 2) {
                    searching = true;
                    [...$rows].forEach(showPermissionRow);
                    $details
                        .filter((element) => !element.getAttribute('open'))
                        .forEach((element) => element.setAttribute('data-drupal-system-state', 'forced-open'));

                    $details.forEach((element) => {
                        element.setAttribute('open', true);
                        hidePackageDetails(element);
                    });

                    var visibleRowCount = [...$rowsAndDetails]
                        .filter((element) => element.style.display === '').length;

                    Drupal.announce(
                        Drupal.t(
                            '!permissions permissions are available in the modified list.',
                            {'!permissions': visibleRowCount}
                        )
                    );
                }
                else if (searching) {
                    searching = false;
                    [...$rowsAndDetails].forEach((row) => {
                        row.style.display = '';
                    });
                    $details
                        .filter((element) => element.getAttribute('data-drupal-system-state') === 'forced-open')
                        .forEach((element) => {
                            element.removeAttribute('data-drupal-system-state');
                            element.setAttribute('open', false);
                        });
                }
            }

            function preventEnterKey(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }

            if ($table) {
                $rowsAndDetails = $table.querySelectorAll('tbody tr, tbody details');
                $rows = $table.querySelectorAll('tbody tr');
                $details = [...$rowsAndDetails].filter((element) => element.classList.contains('js-permissions'));

                $input.addEventListener('keyup', debounce(filterPermissionList, 200));
                $input.addEventListener('keydown', preventEnterKey);
            }
        }
    };

}(Drupal, Drupal.debounce));
