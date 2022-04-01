<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\node\Entity\Node;

/**
 * Provides states handler for entity reference fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_entity_reference_autocomplete_tags",
 * )
 */
class EntityReferenceTags extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    $values_set = $options['values_set'];

    switch ($values_set) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $value_form = $this->getWidgetValue($options['value_form']);
        if ($options['field_cardinality'] == 1) {
          $node = Node::load($value_form);
          if ($node instanceof Node) {
            // Create an array of valid formats of title for autocomplete.
            $state[$options['state']][$options['selector']] = [
              'value' => $this->getAutocompleteSuggestions($node),
            ];
          }
        }
        else {
          $value_form = (array) $value_form;
          $nodes = Node::loadMultiple($value_form);
          if (!empty($nodes)) {
            $suggestion = [];
            foreach (array_values($nodes) as $node) {
              $suggestion[] = $this->getAutocompleteSuggestions($node);
            }
            $state[$options['state']][$options['selector']] = [
              'value' => implode(', ', $suggestion),
            ];
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
   * @return string
   *   An array with a few relevant suggestions for autocomplete.
   */
  private function getAutocompleteSuggestions(Node $node) {
    return $node->label() . ' (' . $node->id() . ')';
  }

  /**
   * Get values from widget settings for plugin.
   *
   * @param array $value_form
   *   Dependency options.
   *
   * @return array
   *   Values for triggering events.
   */
  public function getWidgetValue(array $value_form) {
    if (!empty($value_form)) {
      if (count($value_form['target_id']) > 1) {
        return array_column($value_form['target_id'], 'target_id');
      }

      return $value_form['target_id'][0]['target_id'];
    }
    else {
      return $value_form;
    }
  }

}
