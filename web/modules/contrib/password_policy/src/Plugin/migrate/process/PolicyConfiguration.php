<?php

namespace Drupal\password_policy\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maps D7 policy settings to D9 values.
 *
 * @MigrateProcessPlugin(
 *   id = "policy_configuration"
 * )
 */
class PolicyConfiguration extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The currently running migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a PolicyConfiguration object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The currently running migration.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('migrate.lookup'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source_version = $row->getSourceProperty('source_version');
    foreach ($value['role']['roles'] as $role) {
      // Checks if the policy applies to the role being considered.
      if ($role != 0) {
        $lookup_result = $this->migrateLookup->lookup('d7_user_role', [$role]);
        // Excludes 'anonymous' user role.
        if ($lookup_result['0']['id'] != "anonymous") {
          $value['roles'][$lookup_result['0']['id']] = $lookup_result['0']['id'];
        }
      }
    }
    unset($value['role']);
    // Check if the source site's Password Policy version is 7.x-1.x or 7.x-2.x.
    if ($source_version === 2) {
      $this->transformV2($row, $value);
    }
    else {
      $this->transformV1($value);
    }
    return $value;
  }

  /**
   * Function to convert unit of duration to days.
   *
   * @param string $value
   *   Time in which password expires.
   *
   * @return int
   *   Password expiry in number of days.
   */
  protected static function convert(string $value): int {
    $arr = explode(" ", $value);
    $days = (int) $arr[0];
    if ($arr[1] == "months") {
      $days = $days * 30;
    }
    elseif ($arr[1] == "weeks") {
      $days = $days * 7;
    }
    elseif ($arr[1] == "hours") {
      $days = round($days / 24);
    }
    return $days;
  }

  /**
   * Helper function for version2.
   *
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param array $value
   *   The source value for the migration process plugin.
   */
  private function transformV2(Row $row, &$value) {
    $value['label'] = $row->getSourceProperty('name');
    $value['send_reset_email'] = (bool) $value['expire']['expire_enabled'];
    if ($value['expire']['expire_enabled'] == 1) {
      // The initial format of number of days before a password expire warning
      // email is sent is a comma separated string. A minus sign before the
      // number indicates that the email is to be sent BEFORE expiration.
      // For example: "-14 days, -7 days, -2 days".
      $value['password_reset'] = $this->convert($value['expire']['expire_limit']);
      $value['days'] = explode(", ", $value['expire']['expire_warning_email_sent']);
      $value['days'] = str_replace(" days", "", $value['days']);
      $value['days'] = str_replace("-", "", $value['days']);
      $value['days'] = array_map('intval', $value['days']);
      // After transformation, we get an array of positive integers(no. of days)
      // [14,7,2].
    }

    $value['constraints'] = [
      [
        "id" => "password_length",
        "character_length" => $value['char_count']['char_count'],
        "character_operation" => "minimum",
      ],
      [
        "id" => "consecutive",
        "max_consecutive_characters" => $value['consecutive']['consecutive_char_count'],
      ],
      [
        "id" => "password_policy_character_constraint",
        "character_count" => $value['int_count']['int_count'],
        "character_type" => "numeric",
      ],
      [
        "id" => "password_policy_character_constraint",
        "character_count" => $value['alpha_count']['alpha_count'],
        "character_type" => "letter",
      ],
      [
        "id" => "password_policy_character_constraint",
        "character_count" => $value['special_count']['special_count'],
        "character_type" => "special",
      ],
    ];
    if ($value['username']['username'] === 1) {
      array_push($value['constraints'], [
        "id" => "password_username",
        "disallow_username" => 1,
      ],
      );
    }
  }

  /**
   * Helper function for version1.
   *
   * @param array $value
   *   The source value for the migration process plugin.
   */
  private function transformV1(array &$value) {
    $value['label'] = $value['name'];
    $value['password_reset'] = $value['expiration'];
    $value['send_reset_email'] = ($value['warning'] !== 0) ? 1 : 0;
    $value['days'] = explode(',', $value['warning']);
    $unserialize_constraints = unserialize($value["constraints"]);
    unset($value['constraints']);
    $policy_constraints_index = 0;
    foreach ($unserialize_constraints as $constraint => $count) {
      switch ($constraint) {
        case 'uppercase':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_character_constraint",
            "character_count" => $count,
            "character_type" => "uppercase",
          ];
          break;

        case 'digit':
        case 'digit_placement':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_character_constraint",
            "character_count" => $count,
            "character_type" => "numeric",
          ];
          break;

        case 'history':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_history_constraint",
            "history_repeats" => $count,
          ];
          break;

        case 'character_types':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "character_types",
            "character_types" => $count,
          ];
          break;

        case 'username':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_username",
            "disallow_username" => ($count !== 0) ? 1 : 0,
          ];
          break;

        case 'length':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_length",
            "character_length" => $count,
            "character_operation" => "minimum",
          ];
          break;

        case 'delay':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_delay_constraint",
            "delay" => $count,
          ];
          break;

        case 'punctuation':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_character_constraint",
            "character_count" => $count,
            "character_type" => "special",
          ];
          break;

        case 'lowercase':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_character_constraint",
            "character_count" => $count,
            "character_type" => "lowercase",
          ];
          break;

        case 'letter':
          $value['constraints'][$policy_constraints_index++] = [
            "id" => "password_policy_character_constraint",
            "character_count" => $count,
            "character_type" => "letter",
          ];
          break;
      }
    }
  }

}
