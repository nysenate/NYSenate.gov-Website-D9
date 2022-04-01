<?php

namespace Drupal\twig_field_value\Twig\Extension;

use Drupal\Core\Render\Element;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Provides field value filters for Twig templates.
 */
class FieldValueExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('field_label', [$this, 'getFieldLabel']),
      new \Twig_SimpleFilter('field_value', [$this, 'getFieldValue']),
      new \Twig_SimpleFilter('field_raw', [$this, 'getRawValues']),
      new \Twig_SimpleFilter('field_target_entity', [$this, 'getTargetEntity']),
    ];
  }

  /**
   * Twig filter callback: Only return a field's label.
   *
   * @param array|null $build
   *   Render array of a field.
   *
   * @return string
   *   The label of a field. If $build is not a render array of a field, NULL is
   *   returned.
   */
  public function getFieldLabel($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    return isset($build['#title']) ? $build['#title'] : NULL;
  }

  /**
   * Twig filter callback: Only return a field's value(s).
   *
   * @param array|null $build
   *   Render array of a field.
   *
   * @return array
   *   Array of render array(s) of field value(s). If $build is not the render
   *   array of a field, NULL is returned.
   */
  public function getFieldValue($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    $elements = Element::children($build);
    if (empty($elements)) {
      return NULL;
    }

    $items = [];
    foreach ($elements as $delta) {
      $items[$delta] = $build[$delta];
    }

    return $items;
  }

  /**
   * Twig filter callback: Return specific field item(s) value.
   *
   * @param array|null $build
   *   Render array of a field.
   * @param string $key
   *   The name of the field value to retrieve.
   *
   * @return array|null
   *   Single field value or array of field values. If the field value is not
   *   found, null is returned.
   */
  public function getRawValues($build, $key = '') {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }
    if (!isset($build['#items']) || !($build['#items'] instanceof TypedDataInterface)) {
      return NULL;
    }

    $item_values = $build['#items']->getValue();
    if (empty($item_values)) {
      return NULL;
    }

    $raw_values = [];
    foreach ($item_values as $delta => $values) {
      if ($key) {
        $raw_values[$delta] = isset($values[$key]) ? $values[$key] : NULL;
      }
      else {
        $raw_values[$delta] = $values;
      }
    }

    return count($raw_values) > 1 ? $raw_values : reset($raw_values);
  }

  /**
   * Twig filter callback: Return the referenced entity.
   *
   * Suitable for entity_reference fields: Image, File, Taxonomy, etc.
   *
   * @param array|null $build
   *   Render array of a field.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\ContentEntityInterface[]|null
   *   A single target entity or an array of target entities. If no target
   *   entity is found, null is returned.
   */
  public function getTargetEntity($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }
    if (!isset($build['#field_name'])) {
      return NULL;
    }

    $parent_key = $this->getParentObjectKey($build);
    if (empty($parent_key)) {
      return NULL;
    }

    // Use the parent object to load the target entity of the field.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $build[$parent_key];

    $entities = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $field */
    foreach ($parent->get($build['#field_name']) as $item) {
      if (isset($item->entity)) {
        $entities[] = $item->entity;
      }
    }

    return count($entities) > 1 ? $entities : reset($entities);
  }

  /**
   * Checks whether the render array is a field's render array.
   *
   * @param array|null $build
   *   The render array.
   *
   * @return bool
   *   True if $build is a field render array.
   */
  protected function isFieldRenderArray($build) {

    return isset($build['#theme']) && $build['#theme'] == 'field';
  }

  /**
   * Determine the build array key of the parent object.
   *
   * Different field types use different key names.
   *
   * @param array $build
   *   Render array.
   *
   * @return string
   *   The key.
   */
  private function getParentObjectKey(array $build) {
    $options = ['#object', '#field_collection_item'];
    $parent_key = '';

    foreach ($options as $option) {
      if (isset($build[$option])) {
        $parent_key = $option;
        break;
      }
    }

    return $parent_key;
  }

}
