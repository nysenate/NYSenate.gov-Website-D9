<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of members (usually by session).
 *
 * @OpenlegApiResponse(
 *   id = "member-session list",
 *   label = @Translation("Member Session List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class MemberSessionList extends ResponseSearch {

}
