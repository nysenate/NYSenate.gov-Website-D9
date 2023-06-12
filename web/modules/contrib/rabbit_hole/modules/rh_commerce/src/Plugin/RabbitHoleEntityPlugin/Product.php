<?php

namespace Drupal\rh_commerce\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for commerce products.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_commerce",
 *  label = @Translation("Commerce product"),
 *  entityType = "commerce_product"
 * )
 */
class Product extends RabbitHoleEntityPluginBase {

}
