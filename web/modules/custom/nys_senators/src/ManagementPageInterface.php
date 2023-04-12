<?php

namespace Drupal\nys_senators;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Interface for SenatorManagementPage plugins.
 */
interface ManagementPageInterface extends ContainerFactoryPluginInterface {

  /**
   * Gets the plugin id.
   */
  public function id(): string;

  /**
   * Gets the plugin's page content for the passed senator Term.
   */
  public function getContent(TermInterface $senator): array;

}
