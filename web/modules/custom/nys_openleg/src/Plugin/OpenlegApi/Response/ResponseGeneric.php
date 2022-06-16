<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg\Api\ResponsePluginBase;

/**
 * Generic response wrapper class for Openleg responses.
 *
 * New request plugins should extend this class, or one of the type-specific
 * children, for their own responses.
 *
 * @OpenlegApiResponse(
 *   id = "response_generic",
 *   label = @Translation("Generic Response"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class ResponseGeneric extends ResponsePluginBase {

}
