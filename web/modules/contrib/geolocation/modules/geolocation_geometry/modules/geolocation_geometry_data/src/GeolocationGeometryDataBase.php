<?php

namespace Drupal\geolocation_geometry_data;

use Drupal\Core\Plugin\PluginBase;
use Shapefile\ShapefileReader;
use Shapefile\ShapefileException;

/**
 * Class Geolocation GeometryData Base.
 *
 * @package Drupal\geolocation_geometry_data
 */
abstract class GeolocationGeometryDataBase extends PluginBase {

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
      'progressive' => TRUE,
      'progress_message' => t('Finished step @current / @total.'),
      'finished' => [$this, 'finished'],
      'init_message' => t('Import is starting.'),
      'error_message' => t('Something went horribly wrong.'),
    ];
  }

  /**
   * Download batch callback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Batch return.
   */
  public function download() {
    $definition = $this->getPluginDefinition();
    $destination = \Drupal::service('file_system')->getTempDirectory() . '/' . $this->sourceFilename;

    if (!is_file($destination)) {
      $client = \Drupal::httpClient();
      try {
        $client->get($this->sourceUri, ['save_to' => $destination]);

        \Drupal::messenger()->addMessage(t('File for %name downloaded.', [
          '%name' => $definition['name'],
        ]));
      }
      catch (\Exception $e) {
        return t('ERROR downloading @url. Reason: @reason', [
          '@url' => $this->sourceUri,
          '@reason' => $e->getMessage(),
        ]);
      }
    }
    else {
      \Drupal::messenger()->addMessage(t('File for @name already exists, skipping download.', [
        '@name' => $definition['name'],
      ]));
    }

    if (!empty($this->localDirectory) && substr(strtolower($this->sourceFilename), -3) === 'zip') {
      $zip = new \ZipArchive();
      $res = $zip->open($destination);
      if ($res === TRUE) {
        $zip->extractTo(\Drupal::service('file_system')->getTempDirectory() . '/' . $this->localDirectory);
        $zip->close();

        \Drupal::messenger()->addMessage(t('File for @name unzipped.', [
          '@name' => $definition['name'],
        ]));
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

  /**
   * Finished batch.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   Missed operations.
   * @param string $elapsed
   *   A string representing the elapsed time for the batch process.
   */
  public function finished($success, $results, $operations, $elapsed) {
    $definition = $this->getPluginDefinition();
    if ($success) {
      \Drupal::messenger()->addMessage(t('Success: Imported @results @type elements.', [
        '@results' => count($results),
        '@type' => $definition['name'],
      ]));
    }
    else {
      \Drupal::messenger()->addError(t('ERROR: Imported @results @type elements.', [
        '@results' => count($results),
        '@type' => $definition['name'],
      ]));
    }
  }

}
