(($, Drupal) => {
  function membersBanSelectHandler(event, ui) {
    let valueField = $(event.target);
    if ($(event.target).hasClass('private-message-ban-autocomplete')) {
      const valueFieldName = 'banned_user';
      if ($(`input[name=${valueFieldName}]`).length > 0) {
        valueField = $(`input[name=${valueFieldName}]`);
        // Update the labels too.
        const labels = Drupal.autocomplete.splitValues(event.target.value);
        labels.pop();
        labels.push(ui.item.label);
        event.target.value = labels.join(', ');
      }
    }
    const terms = Drupal.autocomplete.splitValues(valueField.val());
    // Remove the current input.
    terms.pop();
    // Add the selected item.
    terms.push(ui.item.value);

    valueField.val(terms.join(', '));
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  }

  Drupal.behaviors.privateMessageBan = {
    attach() {
      // Attach custom select handler to fields with class.
      $('input.private-message-ban-autocomplete').autocomplete({
        select: membersBanSelectHandler,
      });
    },
  };
})(jQuery, Drupal);
