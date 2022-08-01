/**
 * @file
 * bef_links_use_ajax.js
 *
 * Allows to use ajax with Bef links.
 */

(function ($) {

  // This is only needed to provide ajax functionality
  Drupal.behaviors.better_exposed_filters_select_as_links = {
    attach: function (context, settings) {
      $('.bef-links.bef-links-use-ajax', context).once('bef-links-use-ajax').each(function () {
        let $links = $(this);
        let links_name = $(this).attr('name');
        let $form = $(this).closest('form');
        let $filter = $form.find('input[name=' + links_name + ']');

        $(this).find('a').click(function (event) {
          // Prevent following the link URL.
          event.preventDefault();

          if ($(this).hasClass('bef-link--selected')) {
            // The previously selected link is selected again. Deselect it.
            $(this).removeClass('bef-link--selected');
            $links.find('a[name="' + links_name + '[All]"]').addClass('bef-link--selected');
            $filter.val('All');
          }
          else {
            $links.find('.bef-link--selected').removeClass('bef-link--selected');
            $(this).addClass('bef-link--selected');

            $filter.val($(this)
              .attr('name')
              .substring(links_name.length)
              .replace(/^\[|\]$/g, '') // Trim square brackets.
            );
          }

          // Submit the form.
          $form.find('.form-submit').click();
        });
      });
    }
  };
})(jQuery);
