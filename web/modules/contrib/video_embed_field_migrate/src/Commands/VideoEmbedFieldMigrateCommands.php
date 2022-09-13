<?php

namespace Drupal\video_embed_field_migrate\Commands;

use Drupal\video_embed_field_migrate\VideoEmbedFieldMigrate;
use Drush\Commands\DrushCommands;

/**
 *
 */
class VideoEmbedFieldMigrateCommands extends DrushCommands {

  /**
   * @var \Drupal\video_embed_field_migrate\VideoEmbedFieldMigrate
   */
  protected $videoEmbedMigrate;

  /**
   * @param \Drupal\video_embed_field_migrate\VideoEmbedFieldMigrate $videoEmbedMigrate
   */
  public function __construct(VideoEmbedFieldMigrate $videoEmbedMigrate) {
    parent::__construct();
    $this->videoEmbedMigrate = $videoEmbedMigrate;
  }

  /**
   * Print the fields which will be migrated.
   *
   * @param string $refFieldName
   *   The reference field machine name.
   * @param string $refFieldLabel
   *   The reference field label.
   *
   * @command vef_migrate
   *
   * @filter-default-field name
   */
  public function migrate(string $refFieldName, string $refFieldLabel = 'Video') {
    try {
      $this->videoEmbedMigrate->preFlight();
      $fields = $this->videoEmbedMigrate->findFieldsToMigrate();
      if (count($fields) === 0) {
        $this->io()->error('No fields to migrate! There are no video_embed_field fields attached to non-media entity types in this installation.');
        return;
      }
      $table = $fields;
      foreach ($fields as $index => $field) {
        $values = $this->videoEmbedMigrate->getFieldValues($field['field_name'], $field['entity_type'], $field['bundle']);
        $fields[$index]['values'] = $values;
        $table[$index]['count'] = count($values);
      }
      $this->io()->table(['Field name', 'Entity type', 'Bundle', 'Cardinality', 'No. of values'], $table);
      $mediaType = $this->videoEmbedMigrate->getRemoteVideoMediaType();
      $fieldNameAvailable = $this->videoEmbedMigrate->isFieldNameAvailable($refFieldName, array_unique(array_map(function($field) { return $field['entity_type'];}, $fields)));
      $mediaTypeExistsMsg = !$mediaType ? 'No (it will be created)' : 'Yes (' . $mediaType->label() . ')';
      $fieldNameAvailableMsg = $fieldNameAvailable ? 'Yes' : 'No';
      $this->io()->listing([
        'Remote video media type exists: ' . $mediaTypeExistsMsg,
        'Field name available on all target entity types: ' . $fieldNameAvailableMsg,
      ]);
      if (!$fieldNameAvailable) {
        $this->io()->error("You'll need to choose another field name to proceed");
        return;
      }
      $proceed = $this->io->confirm('Would you like to proceed with the migration?');
      if (!$proceed) {
        return;
      }
      if (!$mediaType) {
        $mediaType = $this->videoEmbedMigrate->createRemoteVideoMediaType();
        $this->io()->success('Media type created!');
      }
      $this->videoEmbedMigrate->createReferenceFields($refFieldName, $fields);

      $fieldsToMigrate = $this->getMigrationItems($fields);
      $this->io()->progressStart(count($fieldsToMigrate));
      foreach ($fieldsToMigrate as $field) {
        $this->videoEmbedMigrate->migrateField($mediaType, $refFieldName, $field['entity_type'], $field['target_id'], $field['field_name']);
        $this->io()->progressAdvance();
      }
      $this->io()->progressFinish();
      $this->io()->success('Migration complete!');
    }
    catch (\Exception $e) {
      $this->io()->error([
        'An error occurred during the migration. This migration should be abandoned.',
        'Please report an issue at https://www.drupal.org/project/video_embed_field_migrate',
        $e->getMessage(),
      ]);
    }
  }

  /**
   * @param array $fields
   *
   * @return array
   */
  protected function getMigrationItems(array $fields) : array {
    $expanded = [];
    foreach ($fields as $field) {
      foreach ($field['values'] as $id => $value) {
        $expanded[] = [
          'field_name' => $field['field_name'],
          'entity_type' => $field['entity_type'],
          'bundle' => $field['bundle'],
          'target_id' => $id,
        ];
      }
    }
    return $expanded;
  }

}
