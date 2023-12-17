<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg_api\AllowHyphensInIds;
use Drupal\nys_openleg_api\RequestPluginBase;

/**
 * Openleg API Request plugin for Agendas.
 *
 * @OpenlegApiRequest(
 *   id = "agenda",
 *   label = @Translation("Agendas"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "agendas"
 * )
 */
class Agenda extends RequestPluginBase {

  use AllowHyphensInIds;

}
