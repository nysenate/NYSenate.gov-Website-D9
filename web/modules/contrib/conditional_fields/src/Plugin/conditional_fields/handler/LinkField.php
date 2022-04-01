<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\Component\Utility\Tags;
use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for links provided by the Link module.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_link_default",
 * )
 */
class LinkField extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   *
   * @todo Provide possibility to create states with pair URL and title.
   *        Only states for URL currently supported.
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $values_array = [];

        // Get an array of values or string for comparing.
        $uri = $this->getWidgetValue($options['value_form']);
        $values = static::getUriAsDisplayableString($uri);

        if (is_array($values)) {
          foreach ($values as $value) {
            $values_array[] = ['value' => $value];
          }
        }
        // Link type = External links only - can be only string.
        else {
          $values_array = [$options['condition'] => $values];
        }

        $state[$options['state']][$options['selector']] = $values_array;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        // Works, there are not implementation here.
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // @todo Send field settings to statesHandler to check field cardinality.
        break;

    }

    return $state;
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string|array
   *   An available values for triggering event.
   *
   * @see LinkWidget::getUserEnteredStringAsUri()
   */
  private static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      $entity_type_manager = \Drupal::entityTypeManager();
      if ($entity_type_manager->getDefinition($entity_type, FALSE) && $entity = $entity_type_manager->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = static::getEntityLabels($entity);
      }
    }

    return $displayable_string;
  }

  /**
   * Returns an array with relevant values for autocomplete in different format.
   *
   * @param object $entity
   *   The entity object.
   *
   * @return array
   *   Relevant values for autocomplete, eg.
   *   - Node title
   *   - Node title (1)
   *   - /node/1
   */
  private static function getEntityLabels($entity) {
    // Use the special view label, since some entities allow the label to be
    // viewed, even if the entity is not allowed to be viewed.
    $title = $entity->label();

    // Labels containing commas or quotes must be wrapped in quotes.
    $title = Tags::encode($title);

    $title_nid = $title . ' (' . $entity->id() . ')';

    $result = [$title, $title_nid, '/node/' . $entity->id()];

    return $result;
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
    return isset($value_form[0]) && isset($value_form[0]['uri']) ? $value_form[0]['uri'] : '';
  }

}
