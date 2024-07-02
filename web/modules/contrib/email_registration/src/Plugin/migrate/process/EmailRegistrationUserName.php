<?php

declare(strict_types=1);

namespace Drupal\email_registration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms the user email to a user name.
 *
 * @code
 * process:
 *   name:
 *     plugin: email_registration_user_name
 *     source: username
 * @endcode
 *
 * Note: unlike normal Email Registration module, this plugin does NOT ensure
 * the username is unique, so be sure to use 'make_unique_entity_field'
 * processor like this:
 *
 * @code
 * process:
 *   format:
 *   -
 *     plugin: email_registration_user_name
 *     source: username
 *   -
 *     plugin: make_unique_entity_field
 *     entity_type: user
 *     field: name
 *     postfix: _
 * @endcode
 *
 * @see email_registration_user_insert().
 *
 * @MigrateProcessPlugin(
 *   id = "email_registration_user_name"
 * )
 */
class EmailRegistrationUserName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Clean and convert mail to username:
    return email_registration_strip_mail_and_cleanup($value);
  }

}
