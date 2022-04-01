<?php

namespace Drupal\conditional_fields\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ConditionalFieldsHandler annotation object.
 *
 * @Annotation
 */
class ConditionalFieldsHandler extends Plugin {
  /**
   * The handler ID.
   *
   * @var string
   */
  public $id;

}
