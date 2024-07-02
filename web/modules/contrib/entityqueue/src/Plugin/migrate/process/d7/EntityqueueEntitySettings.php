<?php

namespace Drupal\entityqueue\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Processes entityqueue entity settings.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_entityqueue_entity_settings"
 * )
 */
class EntityqueueEntitySettings extends ProcessPluginBase{

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $entity_settings = [
      'target_type' => $row->getSourceProperty('target_type'),
      'handler' => 'default:' . $row->getSourceProperty('target_type'),
      'handler_settings' => [
        'target_bundles' => $row->getSourceProperty('settings')['target_bundles'],
        'sort' => [
          'field' => '_none',
        ],
        'auto_create' => FALSE,
        'auto_create_bundle' => '',
      ],
    ];

    return $entity_settings;
  }

}
