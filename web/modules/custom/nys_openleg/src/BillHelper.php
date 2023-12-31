<?php

namespace Drupal\nys_openleg;

/**
 * Helper functions regarding Openleg Bill objects.
 *
 * @todo These really belong somewhere else, as they are dependent on the
 *   Drupal installation and consumption requirements.
 */
class BillHelper {

  /**
   * Generates the canonical title of a bill, with version by default.
   *
   * @param object $item
   *   Ostensibly an OpenLeg representation of a bill object (detail or
   *   summary.  Can be any object with 'session', 'printNo', and
   *   'basePrintNo' properties.
   * @param bool $base
   *   If TRUE, the amendment version is not included.
   * @param string $separator
   *   The separator between session and print number.  Defaults to '-'.
   *
   * @return string
   *   The title, in the form "<session>-<print_number>".
   */
  public static function formatTitle(object $item, bool $base = FALSE, string $separator = '-'): string {
    $num = $base ? ($item->basePrintNo ?? '') : ($item->printNo ?? '');
    $session = $item->session ?? '';
    return ($session && $num) ? $item->session . $separator . $num : '';
  }

  /**
   * Finds senators based on OpenLeg member information.
   *
   * This method expects the memberId to be available in each item.
   *
   * 2023-10-25: secondary matching based on shortname has been removed because
   * shortname is only guaranteed unique within a single session year.  That
   * relationship was never defined in Drupal, and we are unprepared to deal
   * with the inevitable collisions.
   *
   * @param array $items
   *   An array of JSON-decoded member records from OpenLeg.
   *
   * @return array
   *   An array of node IDs for the senators found.
   */
  public static function findSenatorsByMemberInfo(array $items): array {
    $ret = [];
    $member_ids = array_map(
          function ($v) {
              return $v->memberId ?? '';
          },
          $items
      );
    if (count($member_ids)) {
      try {
        $query = \Drupal::entityQuery('taxonomy_term');
        $query->condition('field_ol_member_id', $member_ids, 'IN');
        $ret = $query
          ->accessCheck(FALSE)
          ->execute();
      }
      catch (\Throwable) {
      }
    }

    return array_filter(array_unique($ret));
  }

  /**
   * Finds a Senator taxonomy term based on OpenLeg member ID or shortname.
   *
   * @param object $member_item
   *   A member item from an Openleg response. (e.g., Bill)
   *
   * @return int
   *   A node id, or 0 if none was found.
   */
  public static function findSenatorFromMember(object $member_item): int {
    $found = static::findSenatorsByMemberInfo([$member_item]);
    return (int) reset($found);
  }

}
