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
   * Gets a list of all historical publish dates available in the volume.
   *
   * Note that this list is for the ENTIRE volume, not just this location.
   *
   * @return array
   *   An array of available history markers for the volume.
   */
  public function getAllPublishDates(): array {
    return $this->tree->getVolumePublishDates();
  }

  /**
   * Gets a sorted array of history markers for the current location.
   */
  public function getPublishDates(): array {
    return $this->tree->getPublishDates();
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
    return $this->detail->getLatestActiveDate();
  }

  /**
   * Gets the publish date of the loaded revision (Y-m-d), or an empty string.
   */
  public function getActiveDate(): string {
    return $this->detail->getActiveDate();
  }

}
