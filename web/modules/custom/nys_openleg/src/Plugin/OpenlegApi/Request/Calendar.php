<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Wrapper around ApiRequest for requesting a calendar.
 *
 * @OpenlegApiRequest(
 *   id = "calendar",
 *   label = @Translation("Calendars"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "calendars"
 * )
 */
class Calendar extends RequestPluginBase {

}
