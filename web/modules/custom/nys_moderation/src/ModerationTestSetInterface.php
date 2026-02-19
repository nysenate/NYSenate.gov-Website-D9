<?php

namespace Drupal\nys_moderation;

use Drupal\ai_automators\Entity\AiAutomator;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for Moderation Test Set config entities.
 */
interface ModerationTestSetInterface extends ConfigEntityInterface {

  /**
   * Returns the name/id of the automator being used.
   */
  public function getAutomatorId(): string;

  /**
   * Returns the loaded automator entity.
   */
  public function getAutomator(): AiAutomator;

  /**
   * Returns an associate array of the automator's config.
   *
   * @see web/modules/contrib/ai/modules/ai_automators/src/AiAutomatorEntityModifier.php:186
   */
  public function getAutomatorConfig(): array;

  /**
   * Returns the entity type being tested (e.g., "node").
   */
  public function getTargetType(): string;

  /**
   * Returns an array of entity IDs included in this set.
   */
  public function getTargetList(): array;

  /**
   * Returns a single entity from this set, identified by entity id.
   */
  public function getTarget(mixed $target_id): ?EntityInterface;

  /**
   * If the test set has enough information to run.
   */
  public function isRunnable(): bool;

}
