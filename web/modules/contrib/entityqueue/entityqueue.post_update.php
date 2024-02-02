<?php

/**
 * @file
 * Post update functions for Entityqueue.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Update subqueues to be revisionable and translatable.
 */
function entityqueue_post_update_make_entity_subqueue_revisionable_and_translatable(&$sandbox) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

  $entity_type = $definition_update_manager->getEntityType('entity_subqueue');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('entity_subqueue');

  // Update the entity type definition.
  $entity_keys = $entity_type->getKeys();
  $entity_keys['revision'] = 'revision_id';
  $entity_keys['owner'] = 'uid';
  $entity_keys['revision_translation_affected'] = 'revision_translation_affected';
  $entity_type->set('entity_keys', $entity_keys);
  $entity_type->set('data_table', 'entity_subqueue_field_data');
  $entity_type->set('revision_table', 'entity_subqueue_revision');
  $entity_type->set('revision_data_table', 'entity_subqueue_field_revision');
  $entity_type->set('translatable', TRUE);
  $entity_type->setStorageClass('\Drupal\entityqueue\EntitySubqueueStorage');

  // Update the field storage definitions and add the new ones required by a
  // revisionable and translatable entity type.
  $field_storage_definitions['title']->setRevisionable(TRUE);
  $field_storage_definitions['title']->setTranslatable(TRUE);

  $field_storage_definitions['items']->setRevisionable(TRUE);
  $field_storage_definitions['items']->setTranslatable(TRUE);

  $field_storage_definitions['langcode']->setRevisionable(TRUE);
  $field_storage_definitions['langcode']->setTranslatable(TRUE);

  $field_storage_definitions['uid']->setRevisionable(TRUE);

  $field_storage_definitions['created']->setRevisionable(TRUE);
  $field_storage_definitions['created']->setTranslatable(TRUE);

  $field_storage_definitions['changed']->setRevisionable(TRUE);
  $field_storage_definitions['changed']->setTranslatable(TRUE);

  $field_storage_definitions['revision_id'] = BaseFieldDefinition::create('integer')
    ->setName('revision_id')
    ->setTargetEntityTypeId('entity_subqueue')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision ID'))
    ->setReadOnly(TRUE)
    ->setSetting('unsigned', TRUE);

  $field_storage_definitions['revision_default'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_default')
    ->setTargetEntityTypeId('entity_subqueue')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Default revision'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
    ->setStorageRequired(TRUE)
    ->setInternal(TRUE)
    ->setTranslatable(FALSE)
    ->setRevisionable(TRUE);

  $field_storage_definitions['default_langcode'] = BaseFieldDefinition::create('boolean')
    ->setName('default_langcode')
    ->setTargetEntityTypeId('entity_subqueue')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Default translation'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this is the default translation.'))
    ->setTranslatable(TRUE)
    ->setRevisionable(TRUE)
    ->setDefaultValue(TRUE);

  $field_storage_definitions['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_translation_affected')
    ->setTargetEntityTypeId('entity_subqueue')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision translation affected'))
    ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
    ->setReadOnly(TRUE)
    ->setRevisionable(TRUE)
    ->setTranslatable(TRUE);

  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);

  return t('Entity Subqueues have been converted to be revisionable and translatable.');
}

/**
 * Remove the obsolete 'reverse_in_admin' queue setting.
 */
function entityqueue_post_update_remove_reverse_in_admin_setting() {
  $config_factory = \Drupal::configFactory();

  // Iterate on all queues.
  foreach ($config_factory->listAll('entityqueue.entity_queue.') as $queue_id) {
    $queue_config = $config_factory->getEditable($queue_id);

    $queue_settings = $queue_config->get('queue_settings');
    unset($queue_settings['reverse_in_admin']);
    $queue_settings['reverse'] = FALSE;
    $queue_config->set('queue_settings', $queue_settings);

    $queue_config->save(TRUE);
  }
}
