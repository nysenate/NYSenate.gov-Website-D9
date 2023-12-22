<?php

namespace Drupal\nys_openleg_api;

/**
 * Trait for Openleg Request plugins to normalize the name prior to request.
 */
trait AllowHyphensInIds {

  /**
   * Changes '-' to '/'
   */
  protected function normalizeName(string $name): string {
    return str_replace('-', '/', $name);
  }

}
