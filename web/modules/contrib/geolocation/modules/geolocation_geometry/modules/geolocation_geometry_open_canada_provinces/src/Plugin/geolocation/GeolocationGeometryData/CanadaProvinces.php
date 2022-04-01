<?php

namespace Drupal\geolocation_geometry_open_canada_provinces\Plugin\geolocation\GeolocationGeometryData;

use Shapefile\ShapefileException;
use Drupal\geolocation_geometry_data\GeolocationGeometryDataBase;

/**
 * Import Provinces of Canada.
 *
 * @GeolocationGeometryData(
 *   id = "open_canada_provinces",
 *   name = @Translation("Provinces of Canada"),
 *   description = @Translation("Geometries of all us states."),
 * )
 */
class CanadaProvinces extends GeolocationGeometryDataBase {

  /**
   * {@inheritdoc}
   */
  public $sourceUri = 'https://www.weather.gov/source/gis/Shapefiles/Misc/province.zip';

  /**
   * {@inheritdoc}
   */
  public $sourceFilename = 'province.zip';

  /**
   * {@inheritdoc}
   */
  public $localDirectory = 'geolocation_geometry_open_canadian_provinces';

  /**
   * {@inheritdoc}
   */
  public $shapeFilename = 'province.shp';

  /**
   * {@inheritdoc}
   */
  public function import(&$context) {
    parent::import($context);
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $logger = \Drupal::logger('geolocation_provinces_of_canada');

    try {
      /** @var \Shapefile\Geometry\Geometry $record */
      while ($record = $this->shapeFile->fetchRecord()) {
        if ($record->isDeleted()) {
          continue;
        }

        $name = $record->getData('NAME');
        if (empty($name)) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = $taxonomy_storage->create([
          'vid' => 'geolocation_provinces_of_canada',
          'name' => $name,
        ]);
        $term->set('field_geometry_data_geometry', [
          'geojson' => $record->getGeoJSON(),
        ]);
        $term->save();
      }
      return ('Done importing Provinces of Canada.');
    }
    catch (ShapefileException $e) {
      $logger->warning($e->getMessage());
      return t('Error importing Provinces of Canada.');
    }
  }

}
