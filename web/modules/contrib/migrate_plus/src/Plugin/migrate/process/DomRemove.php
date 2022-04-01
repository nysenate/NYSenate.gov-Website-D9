<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\DomProcessBase;

/**
 * Remove nodes from a DOMDocument object.
 *
 * Configuration:
 * - selector: An XPath selector.
 * - limit: (optional) The maximum number of nodes to remove.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     -
 *       plugin: dom
 *       method: import
 *       source: text_field
 *     -
 *       plugin: dom_remove
 *       selector: //img
 *       limit: 2
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * This example will remove the first two <img> elements from the source text
 * (if there are that many).  Omit 'limit: 2' to remove all <img> elements.
 *
 * @MigrateProcessPlugin(
 *   id = "dom_remove"
 * )
 */
class DomRemove extends DomProcessBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->init($value, $destination_property);
    $walking_dead = [];
    // The PHP docs for removeChild() explain that you need to do this in two
    // steps.
    foreach ($this->xpath->query($this->configuration['selector']) as $node) {
      if (isset($this->configuration['limit']) && count($walking_dead) >= $this->configuration['limit']) {
        break;
      }
      $walking_dead[] = $node;
    }
    foreach ($walking_dead as $node) {
      $node->parentNode->removeChild($node);
    }

    return $this->document;
  }

}
