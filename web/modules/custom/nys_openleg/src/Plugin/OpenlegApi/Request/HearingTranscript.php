<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Wrapper around ApiRequest for requesting a public hearing transcript.
 *
 * @OpenlegApiRequest(
 *   id = "hearing_transcript",
 *   label = @Translation("Public Hearing Transcripts"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "hearings"
 * )
 */
class HearingTranscript extends RequestPluginBase {

}
