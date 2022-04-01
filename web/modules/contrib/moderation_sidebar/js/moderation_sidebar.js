/**
 * @file
 * Contains all javascript logic for moderation_sidebar.
 */

(function ($, Drupal) {

  Drupal.behaviors.moderation_sidebar = {
    attach: function (context, settings) {
      // Re-open the Moderation Sidebar when Quick Edit saves, as the Entity
      // object is stored in form state and we don't want to save something
      // that's outdated.
      $('body').once('moderation-sidebar-init').each(function () {
        if (typeof Drupal.quickedit !== 'undefined' && Drupal.quickedit.collections.entities) {
          Drupal.quickedit.collections.entities.on('change:isCommitting', function (model) {
            if (model.get('isCommitting') === true && $('.moderation-sidebar-container').length) {
              $('.toolbar-icon-moderation-sidebar').trigger('click', {reload: true});
            }
          });
        }
      });
    }
  };

  // Close the sidebar if the toolbar icon is clicked and moderation
  // information is already available.
  $('.toolbar-icon-moderation-sidebar').on('click', function (e, data) {
    if ($('.moderation-sidebar-container').length && (!data || !data.reload)) {
      $('#drupal-off-canvas').dialog('close');
      e.stopImmediatePropagation();
      e.preventDefault();
    }
  });

  $(document).ready(function () {
    $(window).on({
      'dialog:beforecreate': function (event, dialog, $element, settings) {
        if ($element.find('.moderation-sidebar-container').length) {
          $('.toolbar-icon-moderation-sidebar').addClass('sidebar-open');
          settings.dialogClass += ' ui-dialog-off-canvas ui-dialog-moderation-sidebar';
        }
      },
      'dialog:beforeclose': function (event, dialog, $element) {
        if ($element.find('.moderation-sidebar-container').length) {
          $('.toolbar-icon-moderation-sidebar').removeClass('sidebar-open');
        }
      }
    });
  });

})(jQuery, Drupal);
