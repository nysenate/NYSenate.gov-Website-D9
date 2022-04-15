<?php

namespace Drupal\geolocation_geometry_natural_earth_countries\Plugin\geolocation\GeolocationGeometryData;

use Shapefile\ShapefileException;
use Drupal\geolocation_geometry_data\GeolocationGeometryDataBase;

/**
 * Import Countries of the world.
 *
 * @GeolocationGeometryData(
 *   id = "natural_earth_countries",
 *   name = @Translation("Natural Earth Countries"),
 *   description = @Translation("Geometries of all countries in the world."),
 * )
 */
class NaturalEarthCountries extends GeolocationGeometryDataBase {

  /**
   * {@inheritdoc}
   */
  public $sourceUri = 'https://naturalearth.s3.amazonaws.com/110m_cultural/110m_cultural.zip';


  /**
   * {@inheritdoc}
   */
  public $sourceFilename = '110m_cultural.zip';

  /**
   * {@inheritdoc}
   */
  public $localDirectory = 'geolocation_geometry_natural_earth_countries';

  /**
   * {@inheritdoc}
   */
  public $shapeFilename = 'ne_110m_admin_0_countries.shp';

  /**
   * {@inheritdoc}
   */
  public function import(&$context) {
    parent::import($context);
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $logger = \Drupal::logger('geolocation_geometry_natural_earth_countries');

    try {
      /** @var \Shapefile\Geometry\Geometry $record */
      while ($record = $this->shapeFile->fetchRecord()) {
        if ($record->isDeleted()) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = $taxonomy_storage->create([
          'vid' => 'geolocation_geometry_countries',
          'name' => $record->getData('NAME'),
        ]);
        $term->set('field_geometry_data_geometry', [
          'geojson' => $record->getGeoJSON(),
        ]);
        $term->save();
      }
      return t('Done importing Countries.');
    }
    catch (ShapefileException $e) {
      $logger->warning($e->getMessage());
      return t('ERROR importing Countries.');
    }
  }

}
