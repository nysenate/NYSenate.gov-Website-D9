<?php

namespace Drupal\geolocation\Plugin\geolocation\Location;

use Drupal\geolocation\LocationInterface;
use Drupal\geolocation\LocationBase;
use Drupal\geolocation\ViewsContextTrait;

/**
 * Derive center from first row.
 *
 * @Location(
 *   id = "first_row",
 *   name = @Translation("View first row"),
 *   description = @Translation("Use geolocation field value from first row."),
 * )
 */
class FirstRow extends LocationBase implements LocationInterface {

  use ViewsContextTrait;

  /**
   * {@inheritdoc}
   */
  public function getAvailableLocationOptions($context) {
    $options = [];

    if ($displayHandler = self::getViewsDisplayHandler($context)) {
      if ($displayHandler->getPlugin('style')->getPluginId() == 'maps_common') {
        $options['first_row'] = $this->t('First row');
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates($location_option_id, array $location_option_settings, $context = NULL) {
    if (!($displayHandler = self::getViewsDisplayHandler($context))) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }
    $views_style = $displayHandler->getPlugin('style');

    if (empty($views_style->options['geolocation_field'])) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    /** @var \Drupal\geolocation\Plugin\views\field\GeolocationField $geolocation_field */
    $geolocation_field = $views_style->view->field[$views_style->options['geolocation_field']];

    if (empty($geolocation_field)) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    if (empty($views_style->view->result[0])) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    $entity = $geolocation_field->getEntity($views_style->view->result[0]);

    if (empty($entity)) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    if (isset($entity->{$geolocation_field->definition['field_name']})) {

      /** @var \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem $item */
      $item = $entity->{$geolocation_field->definition['field_name']}->first();

      if (empty($item)) {
        return parent::getCoordinates($location_option_id, $location_option_settings, $context);
      }

      return [
        'lat' => $item->get('lat')->getValue(),
        'lng' => $item->get('lng')->getValue(),
      ];
    }

    return parent::getCoordinates($location_option_id, $location_option_settings, $context);
  }

}
