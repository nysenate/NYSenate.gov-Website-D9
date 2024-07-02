<?php

namespace Drupal\session_limit\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Session limit migrate source plugin.
 *
 * @MigrateSource(
 *   id = "session_limit",
 *   source_module = "session_limit"
 * )
 */
class SessionLimit extends Variable {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $rids = $this->select('variable', 'v')
      ->fields('v')
      ->condition('v.name', 'session_limit_rid_%', 'LIKE')
      ->execute();
    foreach ($rids as $item) {
      $row->setSourceProperty($item['name'], unserialize($item['value']));
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    $configuration['variables'] = [
      'session_limit_max',
      'session_limit_masquerade_ignore',
      'session_limit_behaviour',
      'session_limit_logged_out_message_severity',
      'session_limit_include_root_user',
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
  }

}
