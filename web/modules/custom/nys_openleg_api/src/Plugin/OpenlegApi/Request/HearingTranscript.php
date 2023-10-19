<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg_api\RequestPluginBase;

/**
 * Openleg API Request plugin for Public Hearing Transcripts.
 *
 * @OpenlegApiRequestNew(
 *   id = "hearing_transcript",
 *   label = @Translation("Public Hearing Transcripts"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "hearings"
 * )
 */
class HearingTranscript extends RequestPluginBase {

}
