<?php

namespace Drupal\nys_moderation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\nys_moderation\ModerationTestLogInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerTrait;

/**
 * Annotation for the moderation_test_log entity.
 *
 * @ContentEntityType(
 *   id = "moderation_test_log",
 *   label = @Translation("Moderation Test Log"),
 *   label_singular = @Translation("test log"),
 *   label_plural = @Translation("test logs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count log",
 *     plural = "@count logs"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\nys_moderation\ModerationTestLogListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\nys_moderation\Form\ModerationTestLogForm",
 *       "delete" = "Drupal\nys_moderation\Form\ModerationTestLogDeleteForm",
 *     }
 *   },
 *   base_table = "nys_moderation_test_log",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "name" = "name",
 *     "automator" = "automator",
 *     "entity_type" = "entity_type",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *     "moderation_test_log" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/settings/nysenate/ai_moderation/test_logs/{moderation_test_log}",
 *     "collection" = "/admin/settings/nysenate/ai_moderation/test_logs",
 *     "edit-form" = "/admin/settings/nysenate/ai_moderation/test_logs/{moderation_test_log}",
 *     "delete-form" = "/admin/settings/nysenate/ai_moderation/test_logs/{moderation_test_log}/delete",
 *   }
 * )
 */
class ModerationTestLog extends ContentEntityBase implements ModerationTestLogInterface {

  use EntityOwnerTrait;

  /**
   * Caches associated log items.
   *
   * @var array
   */
  protected array $items;

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'user')
      ->setLabel(t('Run By'))
      ->setDescription(t('The user running the test set.'))
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('When the test set was created.'));

    $fields['prompt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Full Prompt'))
      ->setDescription(t('The raw prompt used for this run (no token replacement).'))
      ->setRequired(TRUE);

    $fields['tags'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Tags'))
      ->setDescription(t('Tags or other notes associated with this test run.'))
      ->setSettings(['default_value' => '']);

    return $fields;
  }

  /**
   * {@inheritDoc}
   */
  public function logItems(): array {
    if (!isset($this->items)) {
      try {
        $storage = $this->entityTypeManager()
          ->getStorage('moderation_test_log_item');
        $this->items = $storage->loadByProperties(['log_id' => $this->id()]);
      }
      catch (\Throwable) {
        $this->items = [];
      }
    }
    return $this->items;

  }

  /**
   * {@inheritDoc}
   */
  public function itemCount(): int {
    return count($this->logItems());
  }

  /**
   * {@inheritDoc}
   */
  public function getPassFail(): array {
    // Default return.
    $result = [
      'actual' => ['pass' => 0, 'flag' => 0],
      'expected' => ['pass' => 0, 'flag' => 0],
    ];

    /** @var \Drupal\nys_moderation\Entity\ModerationTestLogItem $entity */
    foreach ($this->logItems() as $entity) {
      $expected = $entity->get('expected')->value ?? 0;
      $actual = $entity->get('passed')->value ?? 0;
      $result['actual'][$actual ? 'pass' : 'flag']++;
      $result['expected'][$expected ? 'pass' : 'flag']++;
    }

    return $result;
  }

  /**
   * Retrieves the User responsible for generating this run.
   */
  public function getRunByUser(): User {
    return $this->get('uid')->entity;
  }

  /**
   * Returns a link to the "run by" User.
   */
  public function getRunByLink(): string {
    try {
      $ret = $this->getRunByUser()->toLink()->toString();
    }
    catch (\Throwable) {
      $ret = '';
    }
    return $ret;
  }

}
