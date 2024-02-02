<?php

namespace Drupal\site_verify\Service;

use \Drupal\Component\Utility\Unicode;

/**
 * Class SiteVerifyService.
 *
 * @package Drupal\site_verify\Service.
 */
class SiteVerifyService {

  /**
   * Menu load callback; loads a site verification record.
   *
   * This also loads the engine details if the record was found.
   *
   * @param int $svid
   *   A site verification ID.
   *
   * @return array
   *   An array of the site verification record, or FALSE if not found.
   */
  public function siteVerifyLoad($svid) {
    $record = \Drupal::database()->select('site_verify', 'site_verify')
      ->fields('site_verify')
      ->condition('svid', $svid)
      ->execute()
      ->fetchAssoc();
    if ($record) {
      $record['engine'] = $this->siteVerifyEngineLoad($record['engine']);
    }

    return $record;
  }

  /**
   * Menu load callback; loads engine details.
   *
   * @param string $engine
   *   A string with the engine shortname.
   *
   * @return array
   *   An arary of the engine details, or FALSE if not found.
   */
  public function siteVerifyEngineLoad($engine) {
    $engines = $this->siteVerifyGetEngines();
    return isset($engines[$engine]) ? $engines[$engine] : FALSE;
  }

  /**
   * Fetch an array of supported search engines.
   */
  public function siteVerifyGetEngines() {
    static $engines;

    if (!isset($engines)) {
      // Fetch the list of engines and allow other modules to alter it.
      $engines = \Drupal::moduleHandler()->invokeAll('site_verify_engine_info');
      \Drupal::moduleHandler()->alter('site_verify_engine', $engines);
      // Merge the default values for each engine entry.
      foreach ($engines as $key => $engine) {
        $engines[$key] += [
          'key' => $key,
          'name' => Unicode::ucfirst($engine['name']),
          'file' => FALSE,
          'file_example' => FALSE,
          'file_validate' => [],
          'file_contents' => FALSE,
          'file_contents_example' => FALSE,
          'file_contents_validate' => [],
          'meta' => FALSE,
          'meta_example' => FALSE,
          'meta_validate' => [],
        ];
      }
    }

    return $engines;
  }

}
