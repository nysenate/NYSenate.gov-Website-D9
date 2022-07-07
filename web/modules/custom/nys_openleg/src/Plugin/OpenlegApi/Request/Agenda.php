<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Wrapper around ApiRequest for requesting an agenda.
 *
 * @OpenlegApiRequest(
 *   id = "agenda",
 *   label = @Translation("Agendas"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "agendas"
 * )
 */
class Agenda extends RequestPluginBase {

}
