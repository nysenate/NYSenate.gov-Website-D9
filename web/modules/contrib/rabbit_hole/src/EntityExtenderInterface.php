<?php

namespace Drupal\rabbit_hole;

/**
 * Defines an interface for entity extender service.
 */
interface EntityExtenderInterface {

  /**
   * Get the extra fields that should be applied to all rabbit hole entities.
   */
  public function getGeneralExtraFields();

}
