<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'sites/all/modules/contrib/password_policy/password_policy.module',
    'name' => 'password_policy',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7208',
    'weight' => '0',
    'info' => 'a:11:{s:4:\"name\";s:15:\"Password Policy\";s:11:\"description\";s:111:\"This module allows you to enforce a specific level of password complexity for the user passwords on the system.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:8:\"Security\";s:12:\"dependencies\";a:1:{i:0;s:6:\"ctools\";}s:5:\"files\";a:5:{i:0;s:27:\"includes/PasswordPolicy.inc\";i:1;s:37:\"includes/PasswordPolicyConstraint.inc\";i:2;s:36:\"includes/PasswordPolicyCondition.inc\";i:3;s:31:\"includes/PasswordPolicyItem.inc\";i:4;s:20:\"password_policy.test\";}s:9:\"configure\";s:35:\"admin/config/people/password_policy\";s:5:\"mtime\";i:1634014599;s:7:\"version\";N;s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}'
  ])
  ->execute();

$connection->schema()->createTable('password_policy', [
  'fields' => [
    'name' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ],
    'config' => [
      'type' => 'text',
      'not null' => FALSE,
    ],
  ],
  'primary key' => array('name'),
]);

$connection->insert('password_policy')
  ->fields(array('name', 'config'))
  ->values([
    'name' => 'test_policy',
    'config' => serialize([
      'past_passwords' => [
        'past_passwords' => '',
      ],
      'delay' => [
        'delay' => '5 hours',
        'threshold' => '1',
      ],
      'int_count' => [
        'int_count' => '2',
      ],
      'consecutive' => [
        'consecutive_char_count' => '3',
      ],
      'drupal_strength' => [
        'drupal_strength' => '50',
      ],
      'username' => [
        'username' => 1,
      ],
      'blacklist' => [
        'blacklist' => '',
        'blacklist_match_substrings' => 0,
      ],
      'alpha_count' => [
        'alpha_count' => '6',
      ],
      'char_count' => [
        'char_count' => '8',
      ],
      'special_count' => [
        'special_count' => 1,
        'special_count_chars' => '',
      ],
      'alpha_case' => [
        'alpha_case' => 1,
      ],
      'role' => [
        'roles' => [
          '1' => 1,
          '2' => 2,
          '3' => 0,
        ]
      ],
      'authmap' => [
        'authmodules' => []
      ],
      'expire' => [
        'expire_enabled' => 1,
        'expire_limit' => '6 months',
        'expire_warning_message' => 'Your password has expired. Please change it now.',
        'expire_warning_email_sent' => '-14 days, -7 days, -2 days',
        'expire_warning_email_message' => '',
        'expire_warning_email_subject' => '[user:name], your password on [site:name] shall expire in [password_expiration_date:interval]',
      ],
    ]),
  ])
  ->execute();
