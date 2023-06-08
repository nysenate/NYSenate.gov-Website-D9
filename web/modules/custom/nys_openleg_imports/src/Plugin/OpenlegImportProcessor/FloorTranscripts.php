<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor;

use Drupal\node\Entity\Node;
use Drupal\nys_openleg\Api\Request;
use Drupal\nys_openleg_imports\ImportProcessorBase;

/**
 * Openleg Import Processor plugin for floor transcripts.
 *
 * @OpenlegImportProcessor(
 *   id = "floor_transcripts",
 *   label = @Translation("Floor Transcripts"),
 *   description = @Translation("Import processor plugin for floor transcripts."),
 *   bundle = "transcript"
 * )
 */
class FloorTranscripts extends ImportProcessorBase {

  /**
   * {@inheritDoc}
   */
  public function transcribeToNode(object $item, Node $node): bool {
    $node->set('field_ol_filename', $this->getId());
    $node->set('field_ol_publish_date', gmdate(Request::OPENLEG_TIME_SIMPLE, strtotime($this->getId())));
    $node->set('field_ol_transcript_type', 'floor');
    $node->set('field_ol_location', $item->location);
    $node->set('field_ol_text', $item->text);
    if ($item->sessionType ?? NULL) {
      $node->set('field_ol_session_type', $item->sessionType);
    }
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getId(): string {
    return $this->item->result()->dateTime ?? '';
  }

}
