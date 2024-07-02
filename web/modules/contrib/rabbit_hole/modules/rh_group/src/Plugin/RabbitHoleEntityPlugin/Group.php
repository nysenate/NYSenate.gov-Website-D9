<?php

namespace Drupal\rh_group\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for Group.
 *
 * @RabbitHoleEntityPlugin(
 *   id = "rh_group",
 *   label = @Translation("Group (deprecated)"),
 *   entityType = "group"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class Group extends RabbitHoleEntityPluginBase {

}
