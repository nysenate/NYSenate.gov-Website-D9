<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor;

use Drupal\node\Entity\Node;
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
    $node->set('field_ol_filename', $this->getId());
    $node->set('field_ol_publish_date', $item->date);
    $node->set('field_ol_transcript_type', 'public_hearing');
    $node->set('field_ol_location', $item->location);
    $node->set('field_ol_text', $item->text);

    $committees = $item->committees ?? [];
    if (count($committees)) {
      $comm_names = array_filter(
            array_unique(
                array_map(
                    function ($v) {
                            return $v->name ?? '';
                    },
                    $committees
                )
            )
        );
      try {
        $refs = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->loadByProperties(['vid' => 'committees', 'name' => $comm_names]);
      }
      catch (\Throwable $e) {
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
