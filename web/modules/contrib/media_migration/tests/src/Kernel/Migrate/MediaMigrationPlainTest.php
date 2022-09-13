<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Core\Extension\ModuleInstallerInterface;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests media migration from non-media source.
 *
 * @group media_migration
 */
class MediaMigrationPlainTest extends MediaMigrationPlainTestBase {

  /**
   * Tests the migration of plain file and image fields to media reference.
   *
   * @dataProvider providerTestPlainFileToMediaMigration
   */
  public function testPlainFileToMediaMigration(bool $classic_node_migration, bool $preexisting_media_types) {
    $this->setClassicNodeMigration($classic_node_migration);

    if ($preexisting_media_types) {
      $this->createStandardMediaTypes(TRUE);
    }

    $this->assertArticleBodyFieldMigrationProcesses($classic_node_migration ? 'd7_node:article' : 'd7_node_complete:article', [
      [
        'plugin' => 'get',
        'source' => 'body',
      ],
      [
        'plugin' => 'media_wysiwyg_filter',
      ],
      [
        'plugin' => 'ckeditor_link_file_to_linkit',
      ],
    ]);

    $this->executeMediaMigrations($classic_node_migration);

    // Check configurations.
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_image', ['image']);
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_image_multi', ['image']);

    // File fields must allow referencing any kind of existing media.
    $media_type_ids = array_keys($this->container->get('entity_type.manager')->getStorage('media_type')->loadMultiple());
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_file', $media_type_ids);
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_file_multi', $media_type_ids);

    // Check media source field config entities.
    $this->assertNonMediaToMediaImageMediaBundleSourceFieldProperties();
    $this->assertNonMediaToMediaDocumentMediaBundleSourceFieldProperties();
    $this->assertNonMediaToMediaAudioMediaBundleSourceFieldProperties();
    $this->assertNonMediaToMediaVideoMediaBundleSourceFieldProperties();

    // Check media entities.
    $this->assertNonMediaToMedia1FieldValues();
    $this->assertNonMediaToMedia2FieldValues();
    $this->assertNonMediaToMedia3FieldValues();
    $this->assertNonMediaToMedia6FieldValues();
    $this->assertNonMediaToMedia7FieldValues();
    $this->assertNonMediaToMedia8FieldValues();
    $this->assertNonMediaToMedia9FieldValues();
    $this->assertNonMediaToMedia10FieldValues();
    $this->assertNonMediaToMedia11FieldValues();
    $this->assertNonMediaToMedia12FieldValues();

    // Check nodes.
    $this->assertNonMediaToMediaNode1FieldValues();
    $this->assertNonMediaToMediaNode2FieldValues();

    $this->assertFilterFormats();
  }

  /**
   * Data provider for ::testPlainFileToMediaMigration().
   *
   * @return array
   *   The test cases.
   */
  public function providerTestPlainFileToMediaMigration() {
    $test_cases = [
      'Classic node migration, no initial media types' => [
        'Classic node migration' => TRUE,
        'Preexisting media types' => FALSE,
      ],
      'Complete node migration, no initial media types' => [
        'Classic node migration' => FALSE,
        'Preexisting media types' => FALSE,
      ],
      'Classic node migration, preexisting media types' => [
        'Classic node migration' => TRUE,
        'Preexisting media types' => TRUE,
      ],
      'Complete node migration, preexisting media types' => [
        'Classic node migration' => FALSE,
        'Preexisting media types' => TRUE,
      ],
    ];

    // Drupal 8.8.x only has 'classic' node migrations.
    // @see https://www.drupal.org/node/3105503
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      $test_cases = array_filter($test_cases, function ($test_case) {
        return $test_case['Classic node migration'];
      });
    }

    return $test_cases;
  }

  /**
   * Tests media entity migration with change tracking enabled.
   */
  public function testChangeTracking() {
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['media_migration_test_change_tracking']);

    $this->testPlainFileToMediaMigration(FALSE, FALSE);

    // Update file with ID 1 (BLUE png).
    $this->sourceDatabase
      ->update('file_managed')
      ->condition('fid', 1)
      ->fields([
        'filename' => 'Blue PNG changed',
      ])
      ->execute();

    // To get clever failure on PHPUnit 9, we need this.
    // @see https://drupal.org/i/3197324
    try {
      $this->executeMediaMigrations();
    }
    catch (\Exception $exception) {
      throw new AssertionFailedError(
        $exception->getMessage() . "\n" .
        $exception->getLine() . "\n" .
        $exception->getTraceAsString()
      );
    }
    $this->assertNonMediaToMedia1FieldValues('Blue PNG changed');
  }

}
