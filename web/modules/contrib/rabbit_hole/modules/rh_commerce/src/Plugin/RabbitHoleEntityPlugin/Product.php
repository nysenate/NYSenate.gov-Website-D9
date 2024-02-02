<?php

namespace Drupal\rh_commerce\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for commerce products.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_commerce",
 *  label = @Translation("Commerce product (deprecated)"),
 *  entityType = "commerce_product"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class Product extends RabbitHoleEntityPluginBase {

}
