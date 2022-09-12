<?php

namespace Drupal\nys_list_formatter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a List Formatter plugin.
 *
 * @Annotation
 */
class ListFormatter extends Plugin {

  /**
   * {@inheritdoc}
   */
  public $field_types = [];

  /**
   * {@inheritdoc}
   */
  public $settings = [];

  /**
   * {@inheritdoc}
   */
  public $module;

}
