<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Wrapper around ApiRequest for requesting a floor transcript.
 *
 * @OpenlegApiRequest(
 *   id = "floor_transcript",
 *   label = @Translation("Floor Transcripts"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "transcripts"
 * )
 */
class FloorTranscript extends RequestPluginBase {

}
