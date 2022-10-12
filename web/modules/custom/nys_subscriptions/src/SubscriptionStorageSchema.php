<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Subscriptions schema handler.
 */
class SubscriptionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   *
   * Adds the composite indexes for source and target entities.
   */
  public function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE): array {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($table = $this->storage->getBaseTable()) {
      $schema[$table]['indexes'] += [
        'subscribe_to__type_id' => [
          'subscribe_to_type',
          'subscribe_to_id',
        ],
        'subscribe_from__type_id' => [
          'subscribe_from_type',
          'subscribe_from_id',
        ],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   *
   * Adds indexes for subscription type and email, and sets the target fields
   * to "NOT NULL".
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == $this->storage->getBaseTable()) {
      switch ($field_name) {
        case 'sub_type':
        case 'email':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'subscribe_to_type':
        case 'subscribe_to_id':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

      }
    }

    return $schema;
  }

}
