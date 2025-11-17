<?php

namespace Drupal\nys_openleg_api;

use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteDetail;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteTree;

/**
 * Consolidation class to combine statute tree and detail items.
 */
class Statute {

  /**
   * Property for the tree.
   *
   * @var \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteTree
   */
  public StatuteTree $tree;

  /**
   * Property for the detail.
   *
   * @var \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteDetail
   */
  public StatuteDetail $detail;

  /**
   * Property to cache the latest revision's activeDate.
   *
   * When viewing a historical revision, $detail->result->activeDate reflects
   * that historical date. This property stores the true latest activeDate
   * for comparison purposes.
   *
   * @var string|null
   */
  protected ?string $latestActiveDate = NULL;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteTree $tree
   *   A StatuteTree Response object.
   * @param \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteDetail|null $detail
   *   A StatuteDetail Response object.  Could be null for non-locations.
   *   (e.g., /laws/ABC vs. /laws/ABC/A1)
   */
  public function __construct(StatuteTree $tree, ?StatuteDetail $detail = NULL) {
    $this->tree = $tree;
    $this->detail = $detail ?? new StatuteDetail();
  }

  /**
   * Gets the document object from the tree, adds the text from the detail.
   *
   * @return object
   *   The document object.
   */
  public function document(): object {
    $ret = $this->tree->documents();
    $ret->text = $this->detail->text();
    return $ret;
  }

  /**
   * Gets the array of child objects.
   */
  public function children(): array {
    return $this->tree->children();
  }

  /**
   * Gets the full title.
   *
   * The full title is an array which includes the current location title, as
   * well as titles of any parent references.
   *
   * @return array
   *   An array of titles, from current location through all parents.
   */
  public function fullTitle(): array {
    $detail = $this->detail->result();
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
    return $this->detail->parents();
  }

  /**
   * Gets a sorted list of history markers for the most recent call.
   *
   * @return array
   *   An array of available history markers.
   */
  public function publishDates(): array {
    return $this->tree->publishDates();
  }

  /**
   * Gets a filtered list of publish dates relevant to the current location.
   *
   * This method filters the full list of volume-wide publish dates to include
   * only those dates up to a specified "most recent" date, typically the
   * latest revision available for the current document.
   *
   * If no most-recent date is provided, it will be inferred from the full
   * publish dates list (the maximum date).
   *
   * Note: The API only provides volume-wide dates, not location-specific ones.
   * This filtering removes future dates but may still include dates that
   * affected other parts of the volume.
   *
   * @param string|null $most_recent_date
   *   (Optional) The most recent date to filter up to. If not provided,
   *   the maximum date from publishedDates is used.
   *
   * @return array
   *   An array of filtered, sorted publish dates.
   */
  public function relevantPublishDates(?string $most_recent_date = NULL): array {
    $all_dates = $this->publishDates();

    // If no most-recent date provided, use the maximum from the list.
    if (!$most_recent_date && !empty($all_dates)) {
      $most_recent_date = max($all_dates);
    }

    // If there's no most-recent date, return all dates (no filtering possible).
    if (!$most_recent_date) {
      return $all_dates;
    }

    // Filter to dates on or before the most recent date.
    $relevant = array_filter(
      $all_dates,
      function ($date) use ($most_recent_date) {
        return $date <= $most_recent_date;
      }
    );

    // Re-sort and return.
    sort($relevant);
    return $relevant;
  }

  /**
   * Gets an array of the siblings associated with the current entry.
   *
   * @return array
   *   The return array has keys for 'previous' and 'next'.
   */
  public function siblings(): array {
    return $this->detail->siblings();
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
    return $this->detail->text($raw);
  }

  /**
   * Sets the latest activeDate (for the most recent revision).
   *
   * @param string $date
   *   The latest activeDate to cache.
   */
  public function setLatestActiveDate(string $date): void {
    $this->latestActiveDate = $date;
  }

  /**
   * Gets the latest activeDate (for the most recent revision).
   *
   * Returns the cached latest date if set, otherwise returns the current
   * detail's activeDate.
   *
   * @return string
   *   The latest activeDate.
   */
  public function getLatestActiveDate(): string {
    return $this->latestActiveDate ?? ($this->detail->result->activeDate ?? '');
  }

}
