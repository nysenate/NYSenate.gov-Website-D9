<?php

namespace Drupal\rh_taxonomy\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for taxonomy.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_taxonomy_term",
 *  label = @Translation("Taxonomy Term (deprecated)"),
 *  entityType = "taxonomy_term"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class Taxonomy extends RabbitHoleEntityPluginBase {

}
