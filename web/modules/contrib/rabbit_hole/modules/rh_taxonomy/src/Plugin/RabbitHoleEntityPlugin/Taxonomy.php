<?php

namespace Drupal\rh_taxonomy\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for taxonomy.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_taxonomy_term",
 *  label = @Translation("Taxonomy Term"),
 *  entityType = "taxonomy_term"
 * )
 */
class Taxonomy extends RabbitHoleEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityTokenMap() {
    return [
      'taxonomy_term' => 'term',
      'taxonomy_vocabulary' => 'vocabulary',
    ];
  }

}
