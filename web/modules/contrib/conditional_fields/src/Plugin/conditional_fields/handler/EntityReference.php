<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\node\Entity\Node;

/**
 * Provides states handler for entity reference fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_entity_reference_autocomplete",
 * )
 */
class EntityReference extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    $values_set = $options['values_set'];

    switch ($values_set) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $value_form = $this->getWidgetValue($options['value_form']);
        if (empty($value_form)) {
          break;
        }
        if ($options['field_cardinality'] == 1) {
          $node = Node::load($value_form[0]['target_id']);
          if ($node instanceof Node) {
            // Create an array of valid formats of title for autocomplete.
            $state[$options['state']][$options['selector']] = $this->getAutocompleteSuggestions($node);
          }
        }
        else {
          $ids = array_column($value_form, 'target_id');
          $nodes = Node::loadMultiple($ids);
          if (!empty($nodes)) {
            foreach (array_values($nodes) as $key => $node) {
              $selector = str_replace('[0]', "[{$key}]", $options['selector']);
              $state[$options['state']][$selector] = $this->getAutocompleteSuggestions($node);
            }
          }
        }
        break;

      default:
        break;
    }

    return $state;
  }

  /**
   * Get a variants of node title for autocomplete.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node object.
   *
   * @return array
   *   An array with a few relevant suggestions for autocomplete.
   */
  private function getAutocompleteSuggestions(Node $node) {
    /** @var \Drupal\node\Entity\Node $node */
    return [
      // Node title (nid).
      ['value' => $node->label() . ' (' . $node->id() . ')'],
      // Node title.
      ['value' => $node->label()],
    ];
  }

  /**
   * Get values from widget settings for plugin.
   *
   * @param array $value_form
   *   Dependency options.
   *
   * @return mixed
   *   Values for triggering events.
   */
  public function getWidgetValue(array $value_form) {
    return $value_form;
  }

}
