<?php

namespace Drupal\private_message\Controller;

use Drupal\Core\Ajax\AjaxResponse;

/**
 * Interface for the Private Message module's AjaxController.
 */
interface AjaxControllerInterface {

  /**
   * Create AJAX responses for JavaScript requests.
   *
   * @param string $op
   *   The type of data to build for the response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response
   */
  public function ajaxCallback($op);

  /**
   * Create AJAX response containing usernames for an autocomplete callback.
   *
   * @param string $target_type
   *   Target type of autocomplete.
   * @param string $selection_handler
   *   Selection handler of autocomplete.
   * @param string $selection_settings_key
   *   Key to the hashed settings.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function privateMessageMembersAutocomplete(string $target_type, string $selection_handler, string $selection_settings_key): AjaxResponse;

}
