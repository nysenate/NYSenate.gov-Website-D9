<?php

namespace Drupal\NYS_Openleg\Api\Search;

use Drupal\NYS_Openleg\Api\ApiRequest;

/**
 * Class Search.
 *
 * Base class for OpenLegislation's Search API.  This must be
 * extended into the specific search types (e.g., Statute).
 *
 * NOTE: OL Search API indices start with 1.  This class does
 * not assume any zero-based values.
 *
 * @package Nys_Openleg\Search
 */
abstract class Search {

  /**
   * @var int Current page number.
   */
  protected int $page;

  /**
   * @var int Number of items per search page.
   */
  protected int $per_page;

  /**
   * @var int Indicates the starting point for paging the returns.
   */
  protected int $offset;

  /**
   * Holds counter information relevant to the current search return.
   * Has array keys for 'start', 'end', and 'total'.
   *
   * @var array
   */
  protected array $count;

  /**
   * @var array Holds the results of the most recent search.
   */
  protected array $data;

  /**
   * @var string The search term for the current search.
   */
  protected string $search_term;

  /**
   * @var string The endpoint to call for a search request.
   */
  protected string $endpoint = '';

  /**
   * Instantiate and execute the search.
   *
   * @param string $search_term
   * @param array $params
   */
  public function __construct(string $search_term, array $params = []) {
    $this->setParams($params);
    $this->execute($search_term);
  }

  /**
   * Sets the parameters for the next Search API call.  The params
   * array recognizes keys for 'page', 'per_page', and 'offset'.
   *
   * @param array $params
   */
  public function setParams($params = []) {
    $this->page = (int) ($params['page'] ?? 1);
    $this->per_page = (int) ($params['per_page'] ?? 10);
    $this->offset = (int) ($params['offset'] ?? 0);
  }

  /**
   * Executes a search request to Openleg, based on the provided search
   * term, or (if blank) the term previously set.
   *
   * @param string $search_term
   */
  public function execute(string $search_term = '') {
    $offset = $this->offset ?: ((($this->page - 1) * $this->per_page) + 1);
    $params = [
      'term' => urlencode($search_term),
      'offset' => $offset,
      'limit' => $this->per_page,
    ];

    $request = new ApiRequest($this->endpoint . '/search');
    $result = $request->get('', $params);
    $this->data = $result->result->items ?? [];
    $this->count = [
      'total' => (int) $result->total,
      'start' => (int) $result->offsetStart,
      'end' => (int) $result->offsetEnd,
    ];
  }

  /**
   * Gets a count stat from the current request.  If a count $type is
   * provided, that individual integer is returned.  Otherwise, the
   * array of count markers is returned.
   *
   * @returns array|int
   */
  public function getCount($type = NULL) {
    if (!is_null($type)) {
      return $this->count[$type] ?? 0;
    }
    else {
      return $this->count;
    }
  }

  /**
   * Get the search result data.
   *
   * @returns array
   */
  public function getResults(): array {
    return $this->data;
  }

}
