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
   * When viewing a historical revision, the publish date (activeDate) reflects
   * that historical date. This property stores the most recent publish date
   * for the location without regard to historical context.
   *
   * @var string
   */
  protected string $latestActiveDate;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteTree $tree
   *   A StatuteTree Response object.
   * @param \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteDetail|null $detail
   *   A StatuteDetail Response object.  Could be null for non-locations.
   *   (e.g., /laws/ABC vs. /laws/ABC/A1)
   * @param string $latestActiveDate
   *   Date (Y-m-d) of the most recent version.  If empty, will be detected.
   */
  public function __construct(StatuteTree $tree, ?StatuteDetail $detail = NULL, string $latestActiveDate = '') {
    $this->tree = $tree;
    $this->detail = $detail ?? new StatuteDetail();
    $this->latestActiveDate = $latestActiveDate ?: $tree->getActiveDate();
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
   * Gets a list of all historical publish dates available in the volume.
   *
   * Note that this list is for the ENTIRE volume, not just this location.
   *
   * @return array
   *   An array of available history markers for the volume.
   */
  public function getAllPublishDates(): array {
    return $this->tree->publishDates();
  }

  /**
   * Gets a subset of publish dates based on a maximum publish date.
   *
   * This method filters the full list to remove any dates later than an
   * arbitrary date $latest_date.  If not passed, the most recent publish
   * date of this location will be used.
   *
   * Note that the full list includes all publish dates for the entire volume.
   * This location may or may not have changed on any particular date found in
   * the return array.
   *
   * @param string $most_recent_date
   *   (Optional) The most recent date to filter up to, as 'Y-m-d'.  If not
   *   provided, this location's latest publish date is used.
   *
   * @return array
   *   An array of filtered, sorted publish dates.
   */
  public function getPublishDates(string $most_recent_date = ''): array {
    $all_dates = $this->getAllPublishDates();

    // If no most-recent date provided, use the location's latest revision.
    if (!$most_recent_date) {
      $most_recent_date = $this->latestActiveDate;
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
   * Gets the publish date of the most recent revision of this location (Y-m-d).
   */
  public function getLatestActiveDate(): string {
    return $this->latestActiveDate;
  }

  /**
   * Gets the publish date of the loaded revision (Y-m-d), or an empty string.
   */
  public function getPublishDate(): string {
    return $this->detail->result->activeDate ?? '';
  }

}
