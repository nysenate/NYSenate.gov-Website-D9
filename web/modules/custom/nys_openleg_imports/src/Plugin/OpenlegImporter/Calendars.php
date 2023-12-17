<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;

/**
 * Openleg Import plugin for calendars.
 *
 * @OpenlegImporter(
 *   id = "calendars",
 *   label = @Translation("Calendars"),
 *   description = @Translation("Import plugin for calendars."),
 *   requester = "calendar"
 * )
 */
class Calendars extends ImporterBase {

}
