<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;

/**
 * Openleg Import plugin for bills and resolutions.
 *
 * @OpenlegImporter(
 *   id = "bills",
 *   label = @Translation("Bills and Resolutions"),
 *   description = @Translation("Import plugin for bills and resolutions."),
 *   requester = "bill"
 * )
 */
class Bills extends ImporterBase {

}
