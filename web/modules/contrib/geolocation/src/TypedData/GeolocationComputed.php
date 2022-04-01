<?php

namespace Drupal\geolocation\TypedData;

use Drupal\Core\TypedData\TypedData;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Class GeolocationComputed.
 *
 * @package Drupal\geolocation
 */
class GeolocationComputed extends TypedData {

  use DependencySerializationTrait;

  /**
   * Cached processed value.
   *
   * @var string
   */
  protected $value = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->value !== NULL) {
      return $this->value;
    }

    /** @var \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem $item */
    $item = $this->getParent();

    if ($item) {
      $lat = trim($item->get('lat')->getValue());
      $lng = trim($item->get('lng')->getValue());

      // Ensure latitude and longitude exist.
      if ($lat !== NULL && $lng !== NULL) {
        // Format the returned value.
        $this->value = $lat . ', ' . $lng;
      }
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->value = $value;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
