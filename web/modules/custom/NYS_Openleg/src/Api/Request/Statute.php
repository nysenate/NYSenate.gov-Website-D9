<?php

namespace Drupal\NYS_Openleg\Api\Request;

use Drupal\NYS_Openleg\Api\ApiRequest;

/**
 *
 */
class Statute {

  /**
   * Decoded JSON object representing an entry's tree.
   *
   * @var object
   *
   * @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-the-law-structure
   */
  public object $tree;

  /**
   * Decoded JSON object representing an entry's detail.
   *
   * @var object
   *
   * @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-a-law-sub-document
   */
  public object $detail;

  /**
   * The Openleg endpoint used for this type of call.
   *
   * @var string
   */
  protected string $endpoint = 'laws';

  /**
   * @var string Stores the book being requested.
   */
  protected string $book = '';

  /**
   * @var mixed|string Stores location being requested.
   */
  protected $location = '';

  /**
   * @var false|string Stores the history marker being requested.
   */
  protected $history = '';

  /**
   * Instantiate and execute the request.
   */
  public function __construct($book, $location = '', $history = '') {
    $this->book = $book;
    $this->location = $location;
    $this->history = $this->resolveHistoryDate($history);

    if ($book) {
      $this->retrieveFull();
    }
  }

  /**
   * Formats a date for proper usage in an OpenLeg request.
   *
   * @param string $date
   *
   * @return string
   */
  protected function resolveHistoryDate(string $date = ''): string {
    if (!$date) {
      $date = $this->history;
    }
    $time = strtotime($date);
    return $time ? (date('Y-m-d', $time) ?: '') : '';
  }

  /**
   * Executes an API call to OpenLeg for the given book, location,
   * and history marker.
   *
   * @param string $book
   * @param string $location
   * @param null $history
   *
   * @return Statute
   */
  public function retrieveFull(string $book = '', string $location = '', $history = NULL): Statute {
    // Reset local properties if a new request is being made.
    if (!is_null($book)) {
      $this->book = $book;
    }
    if (!is_null($location)) {
      $this->location = $location;
    }
    if (!is_null($history)) {
      $this->history = $this->resolveHistoryDate($history);
    }
    $history = $this->history ? ['date' => $this->history] : [];

    $request = new ApiRequest($this->endpoint);
    // Retrieve the law tree.
    $params = array_merge([
      'depth' => 1,
      'fromLocation' => $this->location,
    ], $history);
    $this->tree = $request->get($this->book, $params);

    // Retrieve the law detail.
    $location = $this->location ?: $this->tree->result->documents->locationId;
    $this->detail = $request->get($this->book . '/' . $location, $history);

    return $this;
  }

  /**
   * Gets the document object of the most recent call, including the
   * detail's text as an added property.
   *
   * @return object
   */
  public function document(): object {
    $ret = $this->tree->result->documents ?? (object) [];
    $ret->text = $this->detail->result->text ?? '';
    return $ret;
  }

  /**
   * Gets the array of child objects from the most recent call.
   *
   * @return array
   */
  public function children(): array {
    return $this->tree->result->documents->documents->items ?? [];
  }

  /**
   * Gets an array of the siblings associated with the current
   * entry.  The return array has keys for 'previous' and 'next'.
   *
   * @return array
   */
  public function siblings(): array {
    return [
      'previous' => $this->detail->result->prevSibling ?? NULL,
      'next' => $this->detail->result->nextSibling ?? NULL,
    ];
  }

  /**
   * Gets the full title, including parent references, of the
   * most recent call.
   *
   * @return array
   */
  public function fullTitle(): array {
    $detail = $this->detail->result;
    $parents = array_map(
      function ($v) {
        return $v->docType . ' ' . $v->docLevelId;
      },
      $this->parents()
    );
    $location = $parents
      ? $detail->lawName . ' (' . $detail->lawId . ') ' . implode(', ', $parents)
      : '';
    return [
      $detail->docType . ' ' . $detail->docLevelId,
      $detail->title,
      $location,
    ];
  }

  /**
   * Gets the array of parent objects from the most recent call.
   *
   * @return array
   */
  public function parents(): array {
    return $this->detail->result->parents ?? [];
  }

  /**
   * Gets a sorted list of history markers for the most recent call.
   *
   * @return array
   */
  public function publishDates(): array {
    $sorted = $this->tree->result->publishedDates;
    sort($sorted);
    return $sorted;
  }

  /**
   * Gets the text of the current entry, mangled for proper presentation
   * in HTML.
   *
   * @returns string
   */
  public function text($raw = FALSE): string {
    $ret = $this->detail->result->text ?? '';
    if (!$raw) {
      $ret = str_replace('\\n', '<br />', str_replace('\\n  ', '<br /><br />', htmlentities($ret, ENT_QUOTES)));
    }
    return $ret;
  }

}
