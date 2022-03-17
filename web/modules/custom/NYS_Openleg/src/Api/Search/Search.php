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
 */
abstract class Search {

  /**
   * Current page number.
   *
   * @var int
   */
  protected int $page;

  /**
   * Number of items per search page.
   *
   * @var int
   */
  protected int $shouldNotBeCamelCasePerPage;

  /**
   * The starting point for paging the returns.
   *
   * @var int
   */
  protected int $offset;

  /**
   * Holds counter information relevant to the current search return.
   *
   * Has array keys for 'start', 'end', and 'total'.
   *
   * @var array
   */
  protected array $count;

  /**
   * Holds the results of the most recent search.
   *
   * @var array
   */
  protected array $data;

  /**
   * The search term for the current search.
   *
   * @var string
   */
  protected string $shouldNotBeCamelCaseSearchTerm;

  /**
   * The endpoint to call for a search request.
   *
   * @var string
   */
  protected string $endpoint = '';

  /**
   * Instantiate and execute the search.
   *
   * @param string $search_term
   *   The search term to search for.
   * @param array $params
   *   Array of page options to set.
   */
  public function __construct(string $search_term, array $params = []) {
    $this->setParams($params);
    $this->execute($search_term);
  }

  /**
   * Sets the parameters for the API calls.
   *
   * The params array recognizes keys for 'page', 'per_page', and 'offset'.
   *
   * @param array $params
   *   Array of page options to set.
   */
  public function setParams(array $params = []) {
    $this->page = (int) ($params['page'] ?? 1);
    $this->shouldNotBeCamelCasePerPage = (int) ($params['per_page'] ?? 10);
    $this->offset = (int) ($params['offset'] ?? 0);
  }

  /**
   * Executes a search request to Openleg.
   *
   * Searches for the provided search term, or the term previously set.
   *
   * @param string $search_term
   *   The search term.  If not passed, the last search term set is used.
   */
  public function execute(string $search_term = '') {
    $this->shouldNotBeCamelCaseSearchTerm = $search_term ?: $this->shouldNotBeCamelCaseSearchTerm;
    $offset = $this->offset ?: ((($this->page - 1) * $this->shouldNotBeCamelCasePerPage) + 1);
    $params = [
      'term' => urlencode($this->shouldNotBeCamelCaseSearchTerm),
      'offset' => $offset,
      'limit' => $this->shouldNotBeCamelCasePerPage,
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
   * Gets a count stat from the current request.
   *
   * If a count $type is provided, that individual integer is returned.
   * Otherwise, the array of count markers is returned.
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
