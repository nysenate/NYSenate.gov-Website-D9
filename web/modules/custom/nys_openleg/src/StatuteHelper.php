<?php

namespace Drupal\nys_openleg;

use Drupal\nys_openleg\Api\Request;

/**
 * Class ApiWrapper.
 *
 * A collection of wrapper and meta-functions for Openleg.
 *
 * @todo All of this should probably go in ApiManager.  There are many types of
 *   items being handled, and this class is strictly for Statutes.  Find an
 *   organization strategy to minimize clutter.
 */
class StatuteHelper {

  // Constants for sort order of OpenLeg result sets.
  const SORT_BY_CODE = 1;

  const SORT_BY_NAME = 2;

  // Default URL for statutes.  Can be configured.
  const DEFAULT_LANDING_URL = '/legislation/laws';

  /**
   * Translates the official law type code into a friendly name.
   *
   * As of 2022, there is no canonical source for these names.
   */
  const LAW_TYPE_NAMES = [
    'CONSOLIDATED' => 'Consolidated Laws of New York',
    'UNCONSOLIDATED' => 'Unconsolidated Laws of New York',
    'COURT_ACTS' => 'Court Acts of New York',
    'RULES' => 'Legislative House Rules',
    'MISC' => 'Misc / Other',
  ];

  /**
   * The base path to which the statute pages respond.
   *
   * @var string
   */
  protected static string $landingUrl = '';

  /**
   * Fetches the known law types.
   *
   * Law types are gleaned from the population of books found in OpenLeg API.
   * Returns an array in which the keys are the type code, and values are arrays
   * with name, description, and url.
   *
   * @return array
   *   In the form:
   *   [
   *      'type name' => ['name' => '', 'description' => '', 'url' => ''],
   *      ...,
   *   ]
   */
  public static function getLawTypes(): array {
    // Get the types from the known books if it is not already set.
    if (!$ret = (static::getCache('law-types')->data ?? [])) {
      // Each law type comes pre-formatted as a list item template structure.
      foreach (static::getLawBooks() as $v) {
        $ret[$v->lawType] = $ret[$v->lawType]
                ?? [
                  'name' => ucwords(strtolower(str_replace('_', ' ', $v->lawType ?: 'no description'))),
                  'description' => self::LAW_TYPE_NAMES[$v->lawType] ?? 'no description',
                  'url' => static::baseUrl() . '/' . $v->lawType,
                ];
      }

      // Store for posterity.
      static::setCache('law-types', $ret);
    }

    return $ret;
  }

  /**
   * Fetches a cache value.
   */
  public static function getCache(string $name) {
    return \Drupal::cache()->get('openleg:' . $name);
  }

  /**
   * Clears a cache value.
   */
  public static function clearCache(string $name) {
    \Drupal::cache()->delete('openleg:' . $name);
  }

  /**
   * Fetches a list of all known books of law.
   *
   * The list retrieved from OpenLeg becomes an array, keyed by the book
   * code (e.g., ABP, PEN), with the values being JSON-decoded objects from
   * the response.
   *
   * @return array
   *   An array of book objects.
   *
   * @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-a-list-of-law-ids
   *
   * @todo create a static cache at this level, break out the cache fetch.
   */
  public static function getLawBooks(): array {
    // Check the cache for an existing list.
    $ret = (static::getCache('law-tree')->data) ?? [];

    // Call OpenLeg if the cache is not populated.
    if (!$ret) {
      // Call OpenLeg.
      $response = Request::fetch('laws');
      if ($response && ($response->success ?? FALSE)) {
        // Get the items, and re-organize by lawId.
        foreach (($response->result->items ?? []) as $val) {
          $ret[$val->lawId] = $val;
        }
        ksort($ret);

        // Save the tree in cache.
        static::setCache('law-tree', $ret);
      }
    }

    return $ret;
  }

  /**
   * Sets a cache value, with default retention of 1 day.
   *
   * @param string $name
   *   The cache entry name.
   * @param mixed $data
   *   The cache entry value.
   * @param int $retain
   *   (Optional) Retention time in seconds, defaults to 1 day.
   */
  public static function setCache(string $name, $data, int $retain = 86400) {
    \Drupal::cache()->set('openleg:' . $name, $data, time() + $retain);
  }

  /**
   * Resolves and returns the base URL used by statutes.
   */
  public static function baseUrl(): string {
    if (!static::$landingUrl) {
      static::$landingUrl = \Drupal::config('nys_openleg.settings')
        ->get('base_path') ?: static::DEFAULT_LANDING_URL;
    }
    return static::$landingUrl;
  }

  /**
   * Retrieve all books of a specified type, e.g., CONSOLIDATED.
   *
   * The $sort parameter can be a property name, or one of the pre-defined
   * constants.
   *
   * @param string $type
   *   The type of book to retrieve.
   * @param mixed $sort
   *   (Optional) A constant value, or the name of a property to sort by.
   *
   * @return array
   *   An array of book objects.
   */
  public static function getBooksByType(string $type, $sort = self::SORT_BY_CODE): array {
    $books = array_filter(
          static::getLawBooks(),
          function ($v) use ($type) {
              return $v->lawType == $type;
          }
      );
    return static::sortList($books, $sort);
  }

  /**
   * Sorts an array of objects.
   *
   * The $list parameter is presumably an array of book entries, but any array
   * of objects will suffice.  The $sort parameter can be one of the pre-defined
   * constants, or the name of a property found in each object.  Sort is always
   * implemented as a string comparison.
   *
   * @param array $list
   *   An array of objects.
   * @param mixed $sort
   *   A sort by constant, or property name.
   *
   * @return array
   *   The sorted array.
   */
  public static function sortList(array $list, $sort = self::SORT_BY_CODE): array {
    switch ($sort) {
      case self::SORT_BY_CODE:
        $sort_prop = 'lawId';
        break;

      case self::SORT_BY_NAME:
        $sort_prop = 'name';
        break;

      default:
        $sort_prop = (string) $sort;
        break;
    }
    uasort(
          $list,
          function ($a, $b) use ($sort_prop) {
              return strcmp($a->{$sort_prop}, $b->{$sort_prop});
          }
      );
    return $list;
  }

  /**
   * Generates an array of breadcrumb items.
   *
   * If no law_type is provided, the return will be an empty array.  With a
   * law_type, the return will have at least the top-level breadcrumb.  If
   * parents is also an array, a second breadcrumb pointing to law_type will
   * be added.  If parents is also populated, each entry generates an additional
   * elements.
   *
   * All elements conform to the requirements for rendering with the
   * result-item twig template.
   *
   * @param string $law_type
   *   (Optional) The entry's law type.
   * @param array|null $parents
   *   (Optional) An array of parent entries.
   *
   * @return array
   *   An array of breadcrumb items.
   *
   * @see templates/nys-openleg-result-item.html.twig
   */
  public static function breadcrumbs(string $law_type = '', array $parents = NULL): array {
    $ret = [];
    if ($type_name = (self::LAW_TYPE_NAMES[$law_type] ?? '')) {
      $base_url = static::baseUrl();
      $ret[] = [
        'name' => 'The Laws of New York',
        'url' => $base_url . '/all',
      ];
      if (is_array($parents)) {
        $ret[] = [
          'name' => $type_name,
          'url' => $base_url . '/' . $law_type,
        ];
        foreach ($parents as $v) {
          $ret[] = [
            'name' => $v->docType . ' ' . $v->docLevelId,
            'description' => $v->title,
            'url' => $base_url . '/' . $v->lawId . '/' . $v->locationId,
          ];
        }
      }
    }
    return $ret;
  }

}
