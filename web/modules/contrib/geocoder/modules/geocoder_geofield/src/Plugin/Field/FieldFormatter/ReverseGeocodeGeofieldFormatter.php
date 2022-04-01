<?php

namespace Drupal\geocoder_geofield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geocoder_field\Plugin\Field\FieldFormatter\GeocodeFormatter;
use Geocoder\Model\AddressCollection;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Plugin implementation of the Geocode formatter.
 *
 * @FieldFormatter(
 *   id = "geocoder_geofield_reverse_geocode",
 *   label = @Translation("Reverse geocode"),
 *   field_types = {
 *     "geofield",
 *   }
 * )
 */
class ReverseGeocodeGeofieldFormatter extends GeocodeFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    try {
      $dumper = $this->dumperPluginManager->createInstance($this->getSetting('dumper'));
    }
    catch (PluginException $e) {
      $this->loggerFactory->get('geocoder')->error('No Dumper has been set');
    }
    $providers = $this->getEnabledGeocoderProviders();

    /** @var \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp */
    $geophp = \Drupal::service('geofield.geophp');

    foreach ($items as $delta => $item) {
      /** @var \Geometry $geom */
      $geom = $geophp->load($item->value);

      /** @var \Point $centroid */
      $centroid = $geom->getCentroid();

      if ($address_collection = $this->geocoder->reverse($centroid->y(), $centroid->x(), $providers)) {
        $elements[$delta] = [
          '#markup' => $address_collection instanceof AddressCollection && !$address_collection->isEmpty() ? $dumper->dump($address_collection->first()) : "",
        ];
      }
    }

    return $elements;
  }

}
