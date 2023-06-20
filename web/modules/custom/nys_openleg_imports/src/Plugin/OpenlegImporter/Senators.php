<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;
use Drupal\nys_openleg_imports\ImportResult;

/**
 * Openleg Import plugin for floor transcripts.
 *
 * @OpenlegImporter_NOT_IMPLEMENTED(
 *   id = "senators",
 *   label = @Translation("Senators"),
 *   description = @Translation("Import plugin for senators."),
 *   requester = "member"
 * )
 */
class Senators extends ImporterBase {

  /**
   * This records the process for importing IDs and shortnames from OpenLeg.
   *
   * All members for a given year are returned from OpenLeg.  For each member
   * summary, a search is done for a matching taxonomy term.  A taxonomy term
   * is matched if:
   *   - `vid` == 'senators', and
   *   - field_senator_name.family == <member_summary_object>.shortName, and
   *   - one and only one term is found.
   *
   * If a term is matched, the field_member_shortname and field_member_id are
   * both replaced with the respective values from the summary.  Otherwise, the
   * summary is skipped.
   *
   * @todo Refactor into a service (maybe nys_senators?)
   *
   * Prefer to integrate this with the importer's plugin architecture, but a
   * stand-alone service call outside nys_openleg_imports could work just as
   * well since this is (hopefully) a rare need.  It needs better matching
   * against the full member detail.  Since the search results only provides
   * shortname, a new call for each senator will be needed.  It needs better
   * logic to test for senators with a non-unique last name.  In those cases,
   * the shortname will *usually* be "<last_name><space><first_initial>".
   */
  public function importMemberLinks(string $session): ImportResult {
    $ret = new ImportResult();

    /**
* @var \Drupal\nys_openleg\Service\ApiManager $api
*/
    $api = \Drupal::service('manager.openleg_api');

    /**
* @var \Drupal\Core\Entity\EntityStorageBase $store
*/
    $store = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term');

    $request = $api->getRequest('member');
    /**
* @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\MemberSessionList $members
*/
    $members = $request->retrieve($session, ['limit' => 0]);

    foreach ($members->items() as $member) {
      $shortname = $member->shortName;
      $id = $member->memberId;
      $tax_search = $store->loadByProperties(
            [
              'vid' => 'senator',
              'field_senator_name.family' => $shortname,
            ]
        );
      if (count($tax_search) == 1) {
        $term = reset($tax_search);
        // @phpstan-ignore-next-line
        $term->field_ol_shortname = strtoupper($shortname);
        // @phpstan-ignore-next-line
        $term->field_ol_member_id = (int) $id;
        try {
          $term->save();
          $ret->addSuccess();
        }
        catch (\Throwable $e) {
          $ret->addFail();
        }
      }
      else {
        $ret->addSkip();
      }
    }

    return $ret;
  }

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    return $item->memberId ?? '';
  }

}
