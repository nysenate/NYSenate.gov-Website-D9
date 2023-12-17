<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor;

use Drupal\node\Entity\Node;
use Drupal\nys_openleg_api\Request;
use Drupal\nys_openleg_imports\ImportProcessorBase;

/**
 * Openleg Import Processor plugin for floor transcripts.
 *
 * @OpenlegImportProcessor(
 *   id = "public_hearings",
 *   label = @Translation("Public Hearing Transcripts"),
 *   description = @Translation("Import processor plugin for public hearing
 *   transcripts."), bundle = "transcript"
 * )
 */
class PublicHearings extends ImportProcessorBase {

  /**
   * {@inheritDoc}
   */
  public function transcribeToNode(object $item, Node $node): bool {
    $publish = gmdate(
      Request::OPENLEG_TIME_SIMPLE,
      strtotime($item->date . 'T' . $item->endTime)
    );
    $node->set('field_ol_filename', $this->getId());
    $node->set('field_ol_publish_date', $publish);
    $node->set('field_ol_transcript_type', 'public_hearing');
    $node->set('field_ol_location', $item->address);
    $node->set('field_ol_text', $item->text);

    // This is weird, but at least we'll have the title available.
    $node->set('field_ol_session_type', $item->title ?? '');

    $committees = $item->committees ?? [];
    if (count($committees)) {
      $by_chamber = array_filter(
        $committees,
        function ($v) {
          return strtoupper($v->chamber) == 'SENATE';
        }
      );
      $comm_names = array_filter(array_unique(
        array_map(
          function ($v) {
            return $v->name ?? '';
          },
          $by_chamber
        )
      ));
      try {
        $refs = $this->entityTypeManager->getStorage('taxonomy_term')
          ->loadByProperties(['vid' => 'committees', 'name' => $comm_names]);
      }
      catch (\Throwable) {
        $refs = [];
      }
      $node->set('field_ol_committee', array_keys($refs));
      $node->set('field_ol_committee_names', json_encode($committees));
    }
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getId(): string {
    return $this->item->result()->id ?? '';
  }

}
