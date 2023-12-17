<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg_api\AllowHyphensInIds;
use Drupal\nys_openleg_api\RequestPluginBase;

/**
 * Openleg API Request plugin for Bills.
 *
 * @OpenlegApiRequest(
 *   id = "bill",
 *   label = @Translation("Bills and Resolutions"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "bills"
 * )
 */
class Bill extends RequestPluginBase {

  use AllowHyphensInIds;

}
