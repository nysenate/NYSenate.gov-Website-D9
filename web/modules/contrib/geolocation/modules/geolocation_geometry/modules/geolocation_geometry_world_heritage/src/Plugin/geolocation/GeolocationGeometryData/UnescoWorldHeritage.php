<?php

namespace Drupal\geolocation_geometry_world_heritage\Plugin\geolocation\GeolocationGeometryData;

use Drupal\Component\Utility\Html;
use Drupal\geolocation_geometry_data\GeolocationGeometryDataBase;

/**
 * Import Countries of the world.
 *
 * @GeolocationGeometryData(
 *   id = "unesco_world_heritage",
 *   name = @Translation("UNESCO World Heritage"),
 *   description = @Translation("Points of all UNESCO world heritage sites."),
 * )
 */
class UnescoWorldHeritage extends GeolocationGeometryDataBase {

  /**
   * URI to archive.
   *
   * @var string
   */
  public $sourceUri = 'https://whc.unesco.org/en/list/xml';

  /**
   * Filename of archive.
   *
   * @var string
   */
  public $sourceFilename = 'world_heritage_sites.xml';

  /**
   * {@inheritdoc}
   */
  public function import(&$context) {
    $filename = \Drupal::service('file_system')->getTempDirectory() . '/' . $this->sourceFilename;
    if (!file_exists($filename)) {
      return t('Error importing World heritage sites.');
    }

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach (simplexml_load_file($filename) as $site) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $node = $node_storage->create([
        'type' => 'unesco_world_heritage',
        'title' => Html::decodeEntities(strip_tags($site->site)),
        'field_geometry_data_description' => [
          'value' => $site->short_description,
          'format' => filter_default_format(),
        ],
        'field_geometry_data_point' => [
          'geojson' => '{"type": "Point", "coordinates": [' . $site->longitude . ', ' . $site->latitude . ']}',
        ],
      ]);
      $node->save();
    }

    return t('Done importing World heritage sites.');
  }

}
