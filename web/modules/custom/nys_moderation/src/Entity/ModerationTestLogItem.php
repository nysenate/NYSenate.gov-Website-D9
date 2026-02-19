<?php

namespace Drupal\nys_moderation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\nys_moderation\ModerationTestLogItemInterface;

/**
 * Annotation for the moderation_test_log_item entity.
 *
 * @ContentEntityType(
 *   id = "moderation_test_log_item",
 *   label = @Translation("Moderation Test Log Item"),
 *   label_singular = @Translation("test log item"),
 *   label_plural = @Translation("test log items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count item",
 *     plural = "@count items"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\nys_moderation\ModerationTestLogItemListBuilder",
 *   },
 *   base_table = "nys_moderation_test_log_items",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "automator" = "automator",
 *     "entity_type" = "entity_type",
 *     "moderation_test_log_item" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/settings/nysenate/ai_moderation/log_entry/{moderation_test_log_item}",
 *   }
 * )
 */
class ModerationTestLogItem extends ContentEntityBase implements ModerationTestLogItemInterface {

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['log_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Log Thread ID'))
      ->setSetting('target_type', 'moderation_test_log')
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type being tested.'))
      ->setDefaultValue('node')
      ->setSettings(['max_length' => 64])
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity being tested.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date and time that the test set was run.'));

    $fields['response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response'))
      ->setDescription(t('The raw response from the AI provider.'));

    $fields['expected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('If the content was expected to pass'))
      ->setRequired(TRUE);

    $fields['passed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('If the content passed'))
      ->setRequired(TRUE);

    return $fields;

  }

  /**
   * {@inheritDoc}
   */
  public function getTestedEntity(): ContentEntityInterface {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $ret */
    $ret = $this->entityTypeManager()->getStorage($this->get('entity_type')->value)
      ->load($this->get('entity_id')->target_id);
    return $ret;
  }

}
