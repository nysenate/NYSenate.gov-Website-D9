<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;

/**
 * Openleg Import plugin for public hearing transcripts.
 *
 * @OpenlegImporter(
 *   id = "public_hearings",
 *   label = @Translation("Public Hearing Transcripts"),
 *   description = @Translation("Import plugin for public hearing transcripts."),
 *   requester = "hearing_transcript"
 * )
 */
class PublicHearings extends ImporterBase {

}
