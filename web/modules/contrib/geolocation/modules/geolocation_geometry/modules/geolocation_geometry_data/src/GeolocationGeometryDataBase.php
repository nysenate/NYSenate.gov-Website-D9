<?php

namespace Drupal\geolocation_geometry_data;

use Shapefile\ShapefileReader;
use Shapefile\ShapefileException;

/**
 * Class GeolocationGeometryDataBase.
 *
 * @package Drupal\geolocation_geometry_data
 */
abstract class GeolocationGeometryDataBase {

  /**
   * URI to archive.
   *
   * @var string
   */
  public $sourceUri = '';

  /**
   * Filename of archive.
   *
   * @var string
   */
  public $sourceFilename = '';

  /**
   * Directory extract of archive.
   *
   * @var string
   */
  public $localDirectory = '';

  /**
   * Extracted filename.
   *
   * @var string
   */
  public $shapeFilename = '';

  /**
   * Shape file.
   *
   * @var \Shapefile\ShapefileReader|null
   */
  public $shapeFile;

  /**
   * Return this batch.
   *
   * @return array
   *   Batch return.
   */
  public function getBatch() {
    $operations = [
      [[$this, 'download'], []],
      [[$this, 'import'], []],
    ];

    return [
      'title' => t('Import Shapefile'),
      'operations' => $operations,
      'progress_message' => t('Finished step @current / @total.'),
      'init_message' => t('Import is starting.'),
      'error_message' => t('Something went horribly wrong.'),
    ];
  }

  /**
   * Download batch callback.
   *
   * @return string
   *   Batch return.
   */
  public function download() {
    $destination = \Drupal::service('file_system')->getTempDirectory() . '/' . $this->sourceFilename;

    if (!is_file($destination)) {
      $client = \Drupal::httpClient();
      $client->get($this->sourceUri, ['save_to' => $destination]);
    }

    if (!empty($this->localDirectory) && substr(strtolower($this->sourceFilename), -3) === 'zip') {
      $zip = new \ZipArchive();
      $res = $zip->open($destination);
      if ($res === TRUE) {
        $zip->extractTo(\Drupal::service('file_system')->getTempDirectory() . '/' . $this->localDirectory);
        $zip->close();
      }
      else {
        return t('ERROR downloading @url', ['@url' => $this->sourceUri]);
      }
    }

    return t('Successfully downloaded @url', ['@url' => $this->sourceUri]);
  }

  /**
   * Import batch callback.
   *
   * @param mixed $context
   *   Batch context.
   *
   * @return bool
   *   Batch return.
   */
  public function import(&$context) {
    $logger = \Drupal::logger('geolocation_geometry_data');

    if (empty($this->shapeFilename)) {
      return FALSE;
    }

    try {
      $this->shapeFile = new ShapefileReader(\Drupal::service('file_system')->getTempDirectory() . '/' . $this->localDirectory . '/' . $this->shapeFilename);
    }
    catch (ShapefileException $e) {
      $logger->warning($e->getMessage());
      return FALSE;
    }
    return TRUE;
  }

}
