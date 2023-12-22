<?php

namespace Drupal\nys_openleg_imports;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Interface for Openleg Import Processor plugins.
 */
interface ImportProcessorInterface extends ContainerFactoryPluginInterface {

  /**
   * Initializes the processor with an Openleg item response.
   */
  public function init(ResponsePluginBase $item);

  /**
   * Processes a single item.
   */
  public function process(): bool;

  /**
   * Responsible for transcribing an Openleg item into a Node object.
   *
   * @param object $item
   *   The OpenLeg object being transcribed.
   * @param \Drupal\node\Entity\Node $node
   *   The Drupal node to which the item is being transcribed.
   *
   * @return bool
   *   TRUE if transcription was successful.
   */
  public function transcribeToNode(object $item, Node $node): bool;

}
