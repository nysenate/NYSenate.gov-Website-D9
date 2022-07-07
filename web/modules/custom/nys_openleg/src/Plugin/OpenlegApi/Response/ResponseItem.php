<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg\Api\ResponsePluginBase;

/**
 * Represents an individual item returned from Openleg.
 *
 * @OpenlegApiResponse(
 *   id = "response_item",
 *   label = @Translation("Generic Item Response"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class ResponseItem extends ResponsePluginBase {

}
