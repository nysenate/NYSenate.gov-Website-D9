/**
 * @file
 * Alters jquery.tablesorter behaviour.
 */

(function ($) {

  'use strict';

  $.tablesorter.addParser({
    // Set a unique id.
    id: 'changefreq',
    is: function (s) {
      return false;
    },
    format: function (s) {
      switch (s) {
        case 'always':
          return 0;

        case 'hourly':
          return 1;

        case 'daily':
          return 2;

        case 'weekly':
          return 3;

        case 'monthly':
          return 4;

        case 'yearly':
          return 5;

        default:
          return 6;
      }
    },
    type: 'numeric'
  });

  $(document).ready(function () {
    // Set some location variables.
    var $h1 = $('h1');
    $h1.text($h1.text() + ': ' + location);
    document.title = $h1.text();

    var $table = $('table');
    var options = {widgets: ['zebra']};

    if ($table.hasClass('index')) {
      // Options for sitemap index table.
      options.sortList = [[0, 0]];
    }
    else {
      // Options for sitemap table.
      options.sortList = [[3, 1]];
      options.headers = {
        2: {sorter: 'changefreq'},
        4: {sorter: false },
        5: {sorter: false }
      };
    }

    $table.tablesorter(options);
  });

})(jQuery);
