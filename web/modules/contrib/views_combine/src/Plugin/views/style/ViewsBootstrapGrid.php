<?php

namespace Drupal\views_combine\Plugin\views\style;

use Drupal\views_bootstrap\Plugin\views\style\ViewsBootstrapGrid as ViewsBootstrapGridBase;

/**
 * Default style.
 *
 * @see \Drupal\views_bootstrap\Plugin\views\style\ViewsBootstrapGrid
 */
class ViewsBootstrapGrid extends ViewsBootstrapGridBase {

  use CombineStyleTrait;

}
