<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;

/**
 * Openleg Import plugin for agendas.
 *
 * @OpenlegImporter(
 *   id = "agendas",
 *   label = @Translation("Agendas"),
 *   description = @Translation("Import plugin for agendas."),
 *   requester = "agenda"
 * )
 */
class Agendas extends ImporterBase {

}
