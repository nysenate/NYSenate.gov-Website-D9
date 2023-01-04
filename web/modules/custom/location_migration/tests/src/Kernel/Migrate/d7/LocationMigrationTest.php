<?php

namespace Drupal\Tests\location_migration\Kernel\Migrate\d7;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\location_migration\Traits\LocationMigrationAssertionsTrait;

/**
 * Tests location migrations.
 *
 * @group location_migration
 */
class LocationMigrationTest extends LocationMigrationTestBase {

  use LocationMigrationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'comment',
    'datetime',
    'editor',
    'field',
    'file',
    'filter',
    'geolocation',
    'location_migration',
    'migrate',
    'migrate_drupal',
    'node',
    'options',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function getDatabaseFixtureFilePath(): string {
    return drupal_get_path('module', 'location_migration') . '/tests/fixtures/d7/drupal7_location.php';
  }

  /**
   * Tests the migration of Drupal 7 location fields.
   *
   * @dataProvider providerLocationMigrations
   */
  public function testLocationMigrations(bool $classic_node_migration, array $disabled_source_modules, array $modules_to_install, array $expected_features) {
    if (!empty($disabled_source_modules)) {
      $this->sourceDatabase->update('system')
        ->fields(['status' => 0])
        ->condition('name', $disabled_source_modules, 'IN')
        ->execute();
    }

    if (!empty($modules_to_install)) {
      $module_installer = $this->container->get('module_installer');
      assert($module_installer instanceof ModuleInstallerInterface);
      $module_installer->install($modules_to_install);
    }

    // Execute the relevant migrations.
    $this->executeRelevantMigrations($classic_node_migration, $expected_features['entity']);

    $this->assertTerm1FieldValues($expected_features);
    $this->assertUser2FieldValues($expected_features);
    $this->assertNode1FieldValues($expected_features);
    $this->assertNode2FieldValues($expected_features);
    $this->assertNode3FieldValues($expected_features);
  }

  /**
   * Data provider for ::testLocationMigrations().
   *
   * @return array
   *   The test cases.
   */
  public function providerLocationMigrations() {
    $test_cases = [
      'Classic node migration; with entity location; address, location and email' => [
        'Classic node migration' => TRUE,
        'Disabled source modules' => [],
        'Enabled destination modules' => [],
        'Expected features' => [
          'entity' => TRUE,
          'email' => TRUE,
          'fax' => FALSE,
          'phone' => FALSE,
          'www' => FALSE,
        ],
      ],
      'Classic node migration; with entity location; address, location, email, telephone and link' => [
        'Classic node migration' => TRUE,
        'Disabled source modules' => [],
        'Enabled destination modules' => [
          'link',
          'telephone',
        ],
        'Expected features' => [
          'entity' => TRUE,
          'email' => TRUE,
          'fax' => TRUE,
          'phone' => TRUE,
          'www' => TRUE,
        ],
      ],
      'Classic node migration; no entity location, only address and location' => [
        'Classic node migration' => TRUE,
        'Disabled source modules' => [
          'location_node',
          'location_user',
          'location_taxonomy',
          'location_email',
          'location_fax',
          'location_phone',
          'location_www',
        ],
        'Enabled destination modules' => [],
        'Expected features' => [
          'entity' => FALSE,
          'email' => FALSE,
          'fax' => FALSE,
          'phone' => FALSE,
          'www' => FALSE,
        ],
      ],
      'Classic node migration; no entity location; address, location, email, telephone and link' => [
        'Classic node migration' => TRUE,
        'Disabled source modules' => [
          'location_node',
          'location_user',
          'location_taxonomy',
        ],
        'Enabled destination modules' => [
          'link',
          'telephone',
        ],
        'Expected features' => [
          'entity' => FALSE,
          'email' => TRUE,
          'fax' => TRUE,
          'phone' => TRUE,
          'www' => TRUE,
        ],
      ],
    ];

    // Drupal 8.8.x only has 'classic' node migrations.
    // @see https://www.drupal.org/node/3105503
    if (!version_compare(\Drupal::VERSION, '8.9.x', '<')) {
      foreach ($test_cases as $test_case_label => $provided_data) {
        $new_test_case_label = preg_replace('/^Classic node migration/', 'Complete node migration', $test_case_label);
        $provided_data['Classic node migration'] = FALSE;
        $test_cases[$new_test_case_label] = $provided_data;
      }
    }

    return $test_cases;
  }

}
