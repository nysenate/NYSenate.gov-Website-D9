<?php

namespace Drupal\views_combine\Plugin\views\style;

use Drupal\views\Plugin\views\style\DefaultStyle as DefaultStyleBase;

/**
 * Default style.
 *
 * @see \Drupal\views\Plugin\views\style\DefaultStyle
 */
class DefaultStyle extends DefaultStyleBase {

  use CombineStyleTrait;

}
