<?php

namespace Drupal\rh_media\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for media.
 *
 * @RabbitHoleEntityPlugin(
 *   id = "rh_media",
 *   label = @Translation("Media (deprecated)"),
 *   entityType = "media"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class Media extends RabbitHoleEntityPluginBase {

}
