<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;

/**
 * Openleg Import plugin for floor transcripts.
 *
 * @OpenlegImporter(
 *   id = "floor_transcripts",
 *   label = @Translation("Floor Transcripts"),
 *   description = @Translation("Import plugin for floor transcripts."),
 *   requester = "floor_transcript"
 * )
 */
class FloorTranscripts extends ImporterBase {

}
