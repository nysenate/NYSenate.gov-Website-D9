<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg_api\RequestPluginBase;

/**
 * Openleg API Request plugin for Floor Transcripts.
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
