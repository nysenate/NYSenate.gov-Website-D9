<?php

namespace Drupal\rh_file\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for nodes.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_file",
 *  label = @Translation("File (deprecated)"),
 *  entityType = "file"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class File extends RabbitHoleEntityPluginBase {

}
