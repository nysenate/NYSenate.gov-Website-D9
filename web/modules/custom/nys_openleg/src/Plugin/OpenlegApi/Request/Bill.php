<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Wrapper around ApiRequest for requesting a bill or resolution.
 *
 * @OpenlegApiRequest(
 *   id = "bill",
 *   label = @Translation("Bills and Resolutions"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "bills"
 * )
 */
class Bill extends RequestPluginBase {

}
