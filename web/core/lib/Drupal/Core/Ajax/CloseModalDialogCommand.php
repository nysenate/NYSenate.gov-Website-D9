<?php

namespace Drupal\Core\Ajax;

/**
 * Defines an AJAX command that closes the currently visible modal dialog.
 *
 * @ingroup ajax
 */
class CloseModalDialogCommand extends CloseDialogCommand {

  /**
   * Constructs a CloseModalDialogCommand object.
   *
   * @param bool $persist
   *   (optional) Whether to persist the dialog in the DOM or not.
   * @param null|string $selector
   *   (optional) Selector to scope the modal. Only modals of the same scope
   *   will be removed after opening a subsequent modal.
   */
  public function __construct($persist = FALSE, $selector = NULL) {
    $this->selector = $selector ?: '#drupal-modal';
    $this->persist = $persist;
  }

}
