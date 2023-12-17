<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg_api\AllowHyphensInIds;
use Drupal\nys_openleg_api\RequestPluginBase;

/**
 * Openleg API Request plugin for Calendars.
 *
 * @OpenlegApiRequest(
 *   id = "calendar",
 *   label = @Translation("Calendars"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "calendars"
 * )
 */
class Calendar extends RequestPluginBase {

  use AllowHyphensInIds;

}
