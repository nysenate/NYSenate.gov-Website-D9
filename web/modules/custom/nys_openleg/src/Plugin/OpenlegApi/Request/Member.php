<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Wrapper around ApiRequest for requesting a member.
 *
 * @OpenlegApiRequest(
 *   id = "member",
 *   label = @Translation("Members"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "members"
 * )
 */
class Member extends RequestPluginBase {

}
