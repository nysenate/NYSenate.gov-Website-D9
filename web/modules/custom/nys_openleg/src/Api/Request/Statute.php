<?php

namespace Drupal\nys_openleg\Api\Request;

use Drupal\nys_openleg\Api\ApiRequest;

/**
 * Class Statute.
 *
 * Wrapper around ApiRequest for requesting a single statute.
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
   * The book being requested.
   *
   * @var string
   */
  protected string $book = '';

  /**
   * The location being requested.
   *
   * @var string
   */
  protected string $location = '';

  /**
   * Stores the history marker being requested.
   *
   * @var string
   */
  protected string $history = '';

  /**
   * Instantiate and execute the request.
   *
   * @param string $book
   *   The book to request.
   * @param string $location
   *   (Optional) The location to request.
   * @param string $history
   *   (Optional) The history marker to request.
   */
  public function __construct(string $book, string $location = '', string $history = '') {
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
   *   The date to format, acceptable for strtotime().
   *
   * @return string
   *   The formatted date, or a blank string.
   */
  protected function resolveHistoryDate(string $date = ''): string {
    if (!$date) {
      $date = $this->history;
    }
    $time = strtotime($date);
    return $time ? (date('Y-m-d', $time) ?: '') : '';
  }

  /**
   * Executes an API call to OpenLeg.
   *
   * The passed book, location, and history marker are used for the request,
   * and remembered for later use. If any are not passed, the current settings
   * are used.
   *
   * @param string $book
   *   The book to retrieve.
   * @param string $location
   *   The location to retrieve.
   * @param string|null $history
   *   The history marker to retrieve.
   *
   * @return $this
   */
  public function retrieveFull(string $book = '', string $location = '', string $history = NULL): Statute {
    // Reset local properties if a new request is being made.
    if ($book) {
      $this->book = $book;
    }
    if ($location) {
      $this->location = $location;
    }
    if ($history) {
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
   * Gets the document object.
   *
   * The detail's text property is added to this return.
   *
   * @return object
   *   The document object.
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
   *   Array of child objects.
   */
  public function children(): array {
    return $this->tree->result->documents->documents->items ?? [];
  }

  /**
   * Gets an array of the siblings associated with the current entry.
   *
   * @return array
   *   The return array has keys for 'previous' and 'next'.
   */
  public function siblings(): array {
    return [
      'previous' => $this->detail->result->prevSibling ?? NULL,
      'next' => $this->detail->result->nextSibling ?? NULL,
    ];
  }

  /**
   * Gets the full title.
   *
   * The full title is array which includes the current location title, as
   * well as titles of any parent references.
   *
   * @return array
   *   An array of titles, from current location through all parents.
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
   *   An array of parent objects.
   */
  public function parents(): array {
    return $this->detail->result->parents ?? [];
  }

  /**
   * Gets a sorted list of history markers for the most recent call.
   *
   * @return array
   *   An array of available history markers.
   */
  public function publishDates(): array {
    $sorted = $this->tree->result->publishedDates ?? [];
    sort($sorted);
    return $sorted;
  }

  /**
   * Gets the text of the current entry.
   *
   * If $raw is TRUE, the text is mangled for proper presentation in HTML.
   *
   * @param bool $raw
   *   Indicates if raw text should be returned.
   *
   * @return string
   *   The text.
   */
  public function text(bool $raw = FALSE): string {
    $ret = $this->detail->result->text ?? '';
    if (!$raw) {
      $ret = str_replace('\\n', '<br />', str_replace('\\n  ', '<br /><br />', htmlentities($ret, ENT_QUOTES)));
    }
    return $ret;
  }

}
