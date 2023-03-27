<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;

/**
 * Tests media migration.
 *
 * @group media_migration
 */
class MediaMigrationTest extends MediaMigrationTestBase {

  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'editor',
    'embed',
    'entity_embed',
    'field',
    'file',
    'filter',
    'image',
    'link',
    'media',
    'media_migration',
    'media_migration_test_long_field_name',
    'migmag_process',
    'media_migration_test_oembed',
    'menu_ui',
    'migrate',
    'migrate_drupal',
    'migrate_plus',
    'node',
    'options',
    'smart_sql_idmap',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $withExtraManagedFile101 = TRUE;

  /**
   * Tests the migration of media entities.
   *
   * @dataProvider providerTestMediaMigration
   */
  public function testMediaMigration(string $destination_token, string $reference_method, bool $classic_node_migration, array $expected_node1_embed_attributes, bool $preexisting_media_types) {
    if ($preexisting_media_types) {
      $this->createStandardMediaTypes();
    }
    $this->setEmbedTokenDestinationFilterPlugin($destination_token);
    $this->setEmbedMediaReferenceMethod($reference_method);
    $this->setClassicNodeMigration($classic_node_migration);

    $this->assertArticleBodyFieldMigrationProcesses(
      $classic_node_migration ? 'd7_node:article' : 'd7_node_complete:article',
      [
        [
          'plugin' => 'get',
          'source' => 'body',
        ],
        [
          'plugin' => 'media_wysiwyg_filter',
        ],
        [
          'plugin' => 'img_tag_to_embed',
        ],
      ]
    );

    // Assert that field migration dependencies were added.
    $manager = $this->container->get('plugin.manager.migration');
    assert($manager instanceof MigrationPluginManagerInterface);
    $node_migration = $manager->createInstance($classic_node_migration ? 'd7_node:article' : 'd7_node_complete:article');
    assert($node_migration instanceof Migration);
    // We have to re-index the dependencies.
    $dependencies = array_map(
      function (array $dependencies) {
        natsort($dependencies);
        return array_values(array_unique($dependencies));
      },
      $node_migration->getMigrationDependencies()
    );
    $this->assertEquals([
      'required' => array_values(array_filter([
        'd7_file_entity:image:public',
        'd7_file_entity:video:public',
        'd7_file_entity:video:vimeo',
        'd7_file_entity:video:youtube',
        $this->withExtraManagedFile101 ? 'd7_file_plain:image:public' : NULL,
        'd7_node_type',
        'd7_user',
      ])),
      'optional' => [
        'd7_comment_field_instance',
        'd7_field_instance',
        'd7_file_entity',
        'd7_file_entity:audio:public',
        'd7_file_entity:document:public',
        'd7_file_entity:image:public',
        'd7_file_entity:video:public',
        'd7_file_entity:video:vimeo',
        'd7_file_entity:video:youtube',
        'd7_file_plain',
        'd7_file_plain:image:public',
      ],
    ], $dependencies);

    // Execute the media migrations.
    $this->startCollectingMessages();
    $this->executeMediaMigrations($classic_node_migration);
    $this->assertEmpty($this->migrateMessages);

    // Check configurations.
    $this->assertArticleImageFieldsAllowedTypes();
    $this->assertArticleMediaFieldsAllowedTypes();

    // Test view modes.
    $this->assertMediaAudioDisplayModes();
    $this->assertMediaDocumentDisplayModes();
    $this->assertMediaImageDisplayModes(TRUE);
    $this->assertMediaRemoteVideoDisplayModes();
    $this->assertMediaVideoDisplayModes();

    // Check the migrated media entities.
    $this->assertMedia1FieldValues();
    $this->assertMedia2FieldValues();
    $this->assertMedia3FieldValues();
    $this->assertMedia4FieldValues();
    $this->assertMedia5FieldValues();
    $this->assertMedia6FieldValues();
    $this->assertMedia7FieldValues();
    $this->assertMedia8FieldValues();
    $this->assertMedia9FieldValues();
    $this->assertMedia10FieldValues();
    $this->assertMedia11FieldValues();
    $this->assertMedia12FieldValues();
    if ($this->withExtraManagedFile101) {
      $this->assertMedia101FieldValues();
    }

    $this->assertNode1FieldValues($expected_node1_embed_attributes);

    $this->assertFilterFormats();
  }

  /**
   * Data provider for ::testMediaTokenToMediaEmbedTransform().
   *
   * @return array
   *   The test cases.
   */
  public function providerTestMediaMigration() {
    $default_attributes = [
      'data-entity-type' => 'media',
      'alt' => 'Different alternative text about blue.png in the test article',
      'title' => 'Different title copy for blue.png in the test article',
      'data-align' => 'center',
    ];

    $test_cases = [
      // ID reference method. This should be neutral for media_embed token
      // transform destination.
      'Entity embed destination, ID reference method, classic node migration, preexisting media types' => [
        'Destination filter' => 'entity_embed',
        'Reference method' => 'id',
        'Classic node migration' => TRUE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-id' => '1',
            'data-embed-button' => 'media',
            'data-entity-embed-display' => 'view_mode:media.wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-id' => '7',
            'data-embed-button' => 'media',
            'data-entity-embed-display' => 'view_mode:media.full',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      'Media embed destination, ID reference method, classic node migration, preexisting media types' => [
        'Destination filter' => 'media_embed',
        'Reference method' => 'id',
        'Classic node migration' => TRUE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'default',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      'Entity embed destination, ID reference method, complete node migration, preexisting media types' => [
        'Destination filter' => 'entity_embed',
        'Reference method' => 'id',
        'Classic node migration' => FALSE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-id' => '1',
            'data-embed-button' => 'media',
            'data-entity-embed-display' => 'view_mode:media.wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-id' => '7',
            'data-embed-button' => 'media',
            'data-entity-embed-display' => 'view_mode:media.full',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      'Media embed destination, ID reference method, complete node migration, preexisting media types' => [
        'Destination filter' => 'media_embed',
        'Reference method' => 'id',
        'Classic node migration' => FALSE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'default',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      // UUID reference method.
      'Entity embed destination, UUID reference method, classic node migration, preexisting media types' => [
        'Destination filter' => 'entity_embed',
        'Reference method' => 'uuid',
        'Classic node migration' => TRUE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-uuid' => TRUE,
            'data-embed-button' => 'media',
            'data-entity-embed-display' => 'view_mode:media.wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-entity-embed-display' => 'view_mode:media.full',
            'data-embed-button' => 'media',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      'Media embed destination, UUID reference method, classic node migration, preexisting media types' => [
        'Destination filter' => 'media_embed',
        'Reference method' => 'uuid',
        'Classic node migration' => TRUE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'default',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      'Entity embed destination, UUID reference method, complete node migration, preexisting media types' => [
        'Destination filter' => 'entity_embed',
        'Reference method' => 'uuid',
        'Classic node migration' => FALSE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-uuid' => TRUE,
            'data-embed-button' => 'media',
            'data-entity-embed-display' => 'view_mode:media.wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-entity-embed-display' => 'view_mode:media.full',
            'data-embed-button' => 'media',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      'Media embed destination, UUID reference method, complete node migration, preexisting media types' => [
        'Destination filter' => 'media_embed',
        'Reference method' => 'uuid',
        'Classic node migration' => FALSE,
        'expected_node1_embed_html_attributes' => [
          0 => [
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'wysiwyg',
          ] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-view-mode' => 'default',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
    ];

    // Add 'no initial media types' test cases.
    $test_cases_without_media_types = [];
    foreach ($test_cases as $test_case_label => $test_case) {
      $without_media_label = preg_replace('/preexisting media types$/', 'no media types', $test_case_label);
      $test_case['Preexisting media types'] = FALSE;
      $test_cases_without_media_types[$without_media_label] = $test_case;
    }

    $test_cases += $test_cases_without_media_types;

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
   *
   * @depends testMediaMigration
   */
  public function testChangeTracking() {
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['media_migration_test_change_tracking']);

    $this->testMediaMigration(
      'media_embed',
      'uuid',
      FALSE,
      [
        [
          'data-entity-type' => 'media',
          'data-entity-uuid' => TRUE,
          'data-view-mode' => 'wysiwyg',
          'alt' => 'Different alternative text about blue.png in the test article',
          'title' => 'Different title copy for blue.png in the test article',
          'data-align' => 'center',
        ],
        [
          'data-entity-type' => 'media',
          'data-entity-uuid' => TRUE,
          'data-view-mode' => 'default',
          'alt' => 'A yellow image',
          'title' => 'This is a yellow image',
        ],
      ],
      FALSE
    );

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
    $this->startCollectingMessages();
    $this->executeMediaMigrations();
    $this->assertEmpty($this->migrateMessages);
    $this->assertMedia1FieldValues('Blue PNG changed');
  }

}
