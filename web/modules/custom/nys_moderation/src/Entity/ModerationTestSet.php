<?php

namespace Drupal\nys_moderation\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\ai_automators\Entity\AiAutomator;
use Drupal\ai_automators\Exceptions\AiAutomatorTypeNotFoundException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\nys_moderation\ModerationTestSetInterface;
use Psr\Log\LoggerInterface;

/**
 * Annotation for the moderation_test_set entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_test_set",
 *   label = @Translation("Moderation Test Set"),
 *   label_singular = @Translation("test set"),
 *   label_plural = @Translation("test sets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count set",
 *     plural = "@count sets"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\nys_moderation\ModerationTestSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\nys_moderation\Form\ModerationTestSetForm",
 *       "edit" = "Drupal\nys_moderation\Form\ModerationTestSetForm",
 *       "delete" = "Drupal\nys_moderation\Form\ModerationTestSetDeleteForm",
 *       "run" = "Drupal\nys_moderation\Form\ModerationTestSetRunForm",
 *     }
 *   },
 *   config_prefix = "moderation_test_set",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "automator" = "automator",
 *     "entity_type" = "entity_type",
 *     "moderation_test_set" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "automator",
 *     "entity_type",
 *     "entities"
 *   },
 *   links = {
 *     "canonical" = "/admin/settings/nysenate/ai_moderation/test_sets/{moderation_test_set}",
 *     "collection" = "/admin/settings/nysenate/ai_moderation/test_sets",
 *     "add-form" = "/admin/settings/nysenate/ai_moderation/test_sets/add",
 *     "edit-form" = "/admin/settings/nysenate/ai_moderation/test_sets/{moderation_test_set}",
 *     "delete-form" = "/admin/settings/nysenate/ai_moderation/test_sets/{moderation_test_set}/delete",
 *     "run" = "/admin/settings/nysenate/ai_moderation/test_sets/{moderation_test_set}/run"
 *   }
 * )
 */
class ModerationTestSet extends ConfigEntityBase implements ModerationTestSetInterface {

  use LoggerChannelTrait;

  /**
   * The ID of the automator being used.
   *
   * @var string
   */
  protected string $automator;

  /**
   * The type of entity being tested (e.g., "node").)
   *
   * @var string
   */
  protected string $entity_type;

  /**
   * An array of entity IDs, or null if none are assigned.
   *
   * @var array|null
   */
  protected ?array $entities;

  /**
   * Fully loaded automator.
   *
   * @var \Drupal\ai_automators\Entity\AiAutomator|null
   */
  protected ?AiAutomator $loaded_automator = NULL;

  /**
   * Logging channel for nys_moderation.
   */
  protected LoggerInterface $log;

  /**
   * Constructor.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->log = $this->getLogger('nys_moderation');
  }

  /**
   * {@inheritDoc}
   */
  public function getAutomatorId(): string {
    return $this->automator ?? '';
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\ai_automators\Exceptions\AiAutomatorTypeNotFoundException
   */
  public function getAutomator(): AiAutomator {
    if (!$this->loaded_automator) {
      try {
        $this->loaded_automator = $this->entityTypeManager()
          ->getStorage('ai_automator')
          ->load($this->automator);
      }
      catch (\Throwable $e) {
        $msg = 'AI Automator "' . $this->automator . '" could not be loaded';
        $this->log->critical($msg, ['@reason' => $e->getMessage()]);
        throw new AiAutomatorTypeNotFoundException($e->getMessage(), $e->getCode(), $e);
      }
    }
    return $this->loaded_automator;
  }

  /**
   * {@inheritDoc}
   */
  public function getAutomatorConfig(): array {
    try {
      $automator = $this->getAutomator();
      $config = ['field_name' => $automator->get('field_name')];
    }
    catch (\Throwable $e) {
      $automator = NULL;
      $config = [];
      $this->log->critical('Failed to get automator configuration', ['@msg' => $e->getMessage()]);
    }

    // Compile the automator configuration.
    // @see web/modules/contrib/ai/modules/ai_automators/src/AiAutomatorEntityModifier.php:186
    if ($automator) {
      foreach ($automator->get('plugin_config') as $key => $setting) {
        $config[substr($key, 10)] = $setting;
      }
    }

    return $config;
  }

  /**
   * {@inheritDoc}
   */
  public function getTargetType(): string {
    return $this->entity_type;
  }

  /**
   * {@inheritDoc}
   */
  public function getTargetList(): array {
    return $this->entities;
  }

  /**
   * {@inheritDoc}
   */
  public function getTarget(mixed $target_id): ?ContentEntityInterface {
    try {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $ret */
      $ret = $this->entityTypeManager()
        ->getStorage($this->getTargetType())
        ->load($target_id);
      if (!($ret?->get('field_ai_test')?->value)) {
        $msg = 'Entity @type:@id is not marked as a testable entity';
        $this->log->warning($msg, [
          '@type' => $this->getTargetType(),
          '@id' => $target_id,
          '@test_set' => $this->id(),
        ]);
      }
    }
    catch (\Throwable $e) {
      $msg = 'Entity $type:@id could not be loaded';
      $this->log->error($msg, [
        '@reason' => $e->getMessage(),
        '@id' => $target_id,
        '@type' => $this->getTargetType(),
        '@test_set' => $this->id(),
      ]);
      $ret = NULL;
    }
    return $ret;
  }

  /**
   * {@inheritDoc}
   *
   * Enforce an empty array if no targets are set.
   */
  public function preSave(EntityStorageInterface $storage): void {
    if (!is_array($this->entities)) {
      $this->entities = $this->entities ? [$this->entities] : [];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function isRunnable(): bool {
    return $this->getAutomatorId() && count($this->entities);
  }

}
