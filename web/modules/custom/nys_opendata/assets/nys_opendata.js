(function ($, Drupal, drupalSettings) {

    /**
     * @namespace
     * Initialize all open data tables on load
     */
    Drupal.behaviors.opendata = {
        attach: function (context, settings) {
            // Each table can have custom init settings.  Try to read them.
            jQuery('.managed-csv-datatable-container').each(
                function (i, v) {
                    let $v = jQuery(v),
                    table_id = $v.data('fid'),
                    settings = drupalSettings.opendata.dt_init || {};
                    // Since we are no longer able to pass variables such
                    // as 'js' or 'ajax' to the attached array, add the ajax URL
                    // in JS code. Workaround for CR https://www.drupal.org/node/2383115
                    settings['t_' + table_id]['ajax'] = settings['t_' + table_id]['url'];
                    delete settings['t_' + table_id]['url'];
                    let this_set = settings['t_' + table_id] || settings.default || {};
                    $v.children('.managed-csv-datatable').DataTable(this_set);
                }
            );
        }
    }
})(jQuery, Drupal, drupalSettings);
