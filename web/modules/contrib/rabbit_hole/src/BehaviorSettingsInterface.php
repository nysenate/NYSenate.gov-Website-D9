<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Behavior settings entities.
 */
interface BehaviorSettingsInterface extends ConfigEntityInterface {

  /**
   * Set the configured action.
   *
   * @param string $action
   *   The action to save.
   *
   * @return $this
   */
  public function setAction($action);

  /**
   * Get the configured action.
   *
   * @return string
   *   The action id.
   */
  public function getAction();

  /**
   * Set whether to ignore bypass permissions.
   *
   * @param bool $no_bypass
   *   TRUE - ignore, FALSE - do not ignore.
   *
   * @return $this
   */
  public function setNoBypass(bool $no_bypass);

  /**
   * Get whether to ignore bypass permissions.
   *
   * @return bool
   *   TRUE - ignore, FALSE - do not ignore.
   */
  public function getNoBypass(): bool;

  /**
   * Set whether to show the bypass message.
   *
   * @param bool $bypass_message
   *   TRUE - show message, FALSE - do not show.
   *
   * @return $this
   */
  public function setBypassMessage(bool $bypass_message);

  /**
   * Get whether to ignore bypass permissions.
   *
   * @return bool
   *   TRUE - ignore, FALSE - do not ignore.
   */
  public function getBypassMessage(): bool;

  /**
   * Set plugin configuration.
   *
   * @param array $configuration
   *   Action-specific configuration.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration);

  /**
   * Get plugin configuration.
   *
   * @return array
   *   Action-specific configuration.
   */
  public function getConfiguration(): array;

  /**
   * Returns all settings in array format.
   *
   * @return array
   *   Behavior settings.
   */
  public function getSettings(): array;

}
