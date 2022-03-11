<?php

namespace Drupal\NYS_Openleg;

use Drupal\NYS_Openleg\Api\ApiRequest;

/**
 *
 */
class ApiWrapper {

  // Constants for sort order of OpenLeg result sets.
  const SORT_BY_CODE = 1;

  const SORT_BY_NAME = 2;

  /**
   * An easy reference to the path to which this module responds.
   *
   * @todo this should be dynamic
   */
  const PATH_PREFIX = '/legislation/laws';

  /**
   * Translates the official law type code into a friendly name.
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
   * Sets the API key for future requests.
   *
   * @param string $api_key
   */
  public static function setKey(string $api_key) {
    ApiRequest::useKey($api_key);
  }

  /**
   * Fetches the known law types, based on the population of books
   * from OpenLeg API.  Returns an array in which the keys are the
   * type code, and values are arrays with name, description, and url.
   *
   * @return array In the form:
   *   [
   *      'type name' => ['name' => '', 'description' => '', 'url' => ''],
   *      ...,
   *   ]
   */
  public static function getLawTypes(): array {
    // Get the types from the known books if it is not already set.
    if (!$ret = (static::_check_cache('law-types')->data ?? [])) {
      // Each law type comes pre-formatted as a list item template structure.
      foreach (static::getLawBooks() as $v) {
        $ret[$v->lawType] = $ret[$v->lawType] ?? [
            'name' => ucwords(strtolower(str_replace('_', ' ', $v->lawType ?: 'no description'))),
            'description' => self::LAW_TYPE_NAMES[$v->lawType] ?? 'no description',
            'url' => self::PATH_PREFIX . '/' . $v->lawType,
          ];
      }

      // Store for posterity.
      static::_set_cache('law-types', $ret);
    }

    return $ret;
  }

  /**
   * Fetches a cache value.
   *
   * @param string $name
   *
   * @return mixed
   */
  protected static function _check_cache(string $name) {
    return \Drupal::cache()->get('openleg:' . $name);
  }

  /**
   * Fetches a list of all known books of law from OpenLeg API.  The
   * list becomes an array, keyed by the book code (e.g., ABP, PEN),
   * with the values being JSON-decoded objects from the response.
   *
   * @return array
   *
   * @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-a-list-of-law-ids
   *
   * @todo create a static cache at this level, break out the cache fetch.
   *
   */
  public static function getLawBooks(): array {
    // Check the cache for an existing list.
    $ret = (static::_check_cache('law-tree')->data) ?? [];

    // Call OpenLeg if the cache is not populated.
    if (!$ret) {
      // Call OpenLeg.
      $response = ApiRequest::fetch('laws');
      if ($response && ($response->success ?? FALSE)) {
        // Get the items, and re-organize by lawId.
        foreach (($response->result->items ?? []) as $val) {
          $ret[$val->lawId] = $val;
        }
        ksort($ret);

        // Save the tree in cache.
        static::_set_cache('law-tree', $ret);
      }
    }

    return $ret;
  }

  /**
   * Sets a cache value, with default retention of 1 day.
   *
   * @param string $name
   * @param mixed $data
   * @param int $retain
   */
  protected static function _set_cache(string $name, $data, int $retain = 86400) {
    \Drupal::cache()->set('openleg:' . $name, $data, time() + $retain);
  }

  /**
   * Retrieve all books belonging to the passed type, e.g., CONSOLIDATED.
   * The $sort parameter can be a property name, or one of the pre-defined
   * constants.
   *
   * @param string $type
   * @param mixed $sort
   *
   * @return array
   */
  public static function getBooksByType($type, $sort = self::SORT_BY_CODE): array {
    $books = array_filter(
      static::getLawBooks(),
      function ($v) use ($type) {
        return $v->lawType == $type;
      }
    );
    return static::sortList($books, $sort);
  }

  /**
   * Sorts an array of objects, presumably an array of book entries, but
   * any array of objects will suffice.  The $sort parameter can be one of
   * the pre-defined constants, or the name of a property found in each
   * object.  Sort is always implemented as a string comparison.
   *
   * @param array $list
   * @param mixed $sort
   *
   * @return array
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
   * Generates an array of breadcrumb items.  If no law_type is provided,
   * the return will be an empty array.  With a law_type, the return will
   * have at least the top-level breadcrumb.  If parents is also an array,
   * a second breadcrumb pointing to law_type will be added.  If parents
   * is also populated, each entry generates an additional elements.
   *
   * All elements conform to the requirements for rendering with the
   * result-item twig template.
   *
   * @param string $law_type
   * @param ?array $parents
   *
   * @return array
   *
   * @see templates/nys-openleg-result-item.html.twig
   */
  public static function breadcrumbs(string $law_type = '', array $parents = NULL): array {
    $ret = [];
    if ($type_name = (self::LAW_TYPE_NAMES[$law_type] ?? '')) {
      $ret[] = [
        'name' => 'The Laws of New York',
        'url' => '/legislation/laws/all',
      ];
      if (is_array($parents)) {
        $ret[] = [
          'name' => $type_name,
          'url' => self::PATH_PREFIX . '/' . $law_type,
        ];
        foreach ($parents as $v) {
          $ret[] = [
            'name' => $v->docType . ' ' . $v->docLevelId,
            'description' => $v->title,
            'url' => self::PATH_PREFIX . '/' . $v->lawId . '/' . $v->locationId,
          ];
        }
      }
    }
    return $ret;

  }

}
