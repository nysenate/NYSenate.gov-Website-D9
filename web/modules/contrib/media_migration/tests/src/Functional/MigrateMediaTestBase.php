<?php

namespace Drupal\Tests\media_migration\Functional;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_migration\MediaMigration;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;
use Drupal\Tests\media_migration\Traits\MediaMigrationTestTrait;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;
use Drupal\Tests\WebAssert;

/**
 * Provides a base class for testing media migration via the UI.
 */
abstract class MigrateMediaTestBase extends MigrateUpgradeTestBase {

  use StringTranslationTrait;
  use MediaMigrationTestTrait;
  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * The method how embed code should reference media entities.
   *
   * This might be 'id', or 'uuid'.
   *
   * @var string|null
   */
  protected $embedMediaReferenceMethod;

  /**
   * The destination filter plugin ID to target on entity embed token transform.
   *
   * If this is not null, then the corresponding "$settings" key is written into
   * the settings.php file.
   *
   * @var string|null
   */
  protected $embedTokenDestinationFilterPlugin;

  /**
   * The destination site major version.
   *
   * @var string
   */
  protected $destinationSiteVersion;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'media',
    'media_migration',
    'media_migration_test_oembed',
    'migrate_drupal_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourcePrivateFilesPath() {
    return $this->getSourceBasePath();
  }

  /**
   * Returns IDs or labels of those entities that shouldn't be checked.
   *
   * @return string[][]
   *   An array of expected entity labels keyed by IDs, grouped by entity type
   *   ID. For some of the entities, label can be NULL.
   */
  protected function getIgnoredEntities() {
    return [
      'file' => [
        'audio.png',
        'generic.png',
        'video.png',
      ],
    ];
  }

  /**
   * Gets the expected entity IDs and labels per entity type after migration.
   *
   * @return string|null[][]
   *   An array of expected entity labels keyed by IDs, grouped by entity type
   *   ID. For some of the entities, label can be NULL.
   */
  protected function getExpectedEntities() {
    $expected_entities = [
      'editor' => [
        'basic_html' => 'Basic HTML',
        'full_html' => 'Full HTML',
      ],
      'entity_form_display' => [
        'block_content.basic.default' => NULL,
        'comment.comment.default' => NULL,
        'comment.comment_node_article.default' => NULL,
        'comment.comment_node_page.default' => NULL,
        'media.audio.default' => NULL,
        'media.document.default' => NULL,
        'media.image.default' => NULL,
        'media.remote_video.default' => NULL,
        'media.video.default' => NULL,
        'node.article.default' => NULL,
        'node.page.default' => NULL,
        'user.user.default' => NULL,
      ],
      'entity_form_mode' => [
        'user.register' => 'Register',
      ],
      'entity_view_display' => [
        'block_content.basic.default' => NULL,
        'comment.comment.default' => NULL,
        'comment.comment_node_article.default' => NULL,
        'comment.comment_node_page.default' => NULL,
        'media.audio.default' => NULL,
        'media.document.default' => NULL,
        'media.image.default' => NULL,
        'media.remote_video.default' => NULL,
        'media.video.default' => NULL,
        'node.article.default' => NULL,
        'node.article.teaser' => NULL,
        'node.page.default' => NULL,
        'node.page.teaser' => NULL,
        'user.user.compact' => NULL,
        'user.user.default' => NULL,
      ],
      'entity_view_mode' => [
        'block_content.full' => 'Full',
        'comment.full' => 'Full',
        'media.full' => 'Full',
        'media.preview' => 'preview',
        'media.rss' => 'RSS',
        'media.search_index' => 'Search index',
        'media.search_result' => 'Search result',
        'media.teaser' => 'Teaser',
        'media.wysiwyg' => 'WYSIWYG',
        'node.full' => 'Full',
        'node.rss' => 'RSS',
        'node.search_index' => 'Search index',
        'node.search_result' => 'Search result highlighting input',
        'node.teaser' => 'Teaser',
        'taxonomy_term.full' => 'Taxonomy term page',
        'user.compact' => 'Compact',
        'user.full' => 'User account',
      ],
      'field_config' => [
        'block_content.basic.body' => 'Body',
        'comment.comment.comment_body' => 'Comment',
        'comment.comment_node_article.comment_body' => 'Comment',
        'comment.comment_node_page.comment_body' => 'Comment',
        'media.audio.field_media_audio_file' => 'Audio file',
        'media.document.field_media_document' => 'Document',
        'media.image.field_media_image' => 'Image',
        'media.image.field_media_integer' => 'Integer',
        'media.remote_video.field_media_oembed_video' => 'Video URL',
        'media.video.field_media_video_file' => 'Video file',
        'node.article.body' => 'Body',
        'node.article.comment_node_article' => 'Comments',
        'node.article.field_image' => 'Image',
        'node.article.field_media' => 'Media',
        'node.page.body' => 'Body',
        'node.page.comment_node_page' => 'Comments',
        'user.user.user_picture' => 'Picture',
      ],
      'field_storage_config' => [
        'block_content.body' => 'block_content.body',
        'comment.comment_body' => 'comment.comment_body',
        'media.field_media_audio_file' => 'media.field_media_audio_file',
        'media.field_media_document' => 'media.field_media_document',
        'media.field_media_image' => 'media.field_media_image',
        'media.field_media_integer' => 'media.field_media_integer',
        'media.field_media_oembed_video' => 'media.field_media_oembed_video',
        'media.field_media_video_file' => 'media.field_media_video_file',
        'node.body' => 'node.body',
        'node.comment_node_article' => 'node.comment_node_article',
        'node.comment_node_page' => 'node.comment_node_page',
        'node.field_image' => 'node.field_image',
        'node.field_media' => 'node.field_media',
        'user.user_picture' => 'user.user_picture',
      ],
      'filter_format' => [
        'basic_html' => 'Basic HTML',
        'full_html' => 'Full HTML',
        'plain_text' => 'Plain text',
        'restricted_html' => 'Restricted HTML',
        'filtered_html' => 'Filtered HTML',
      ],
      'image_style' => [
        'large' => 'Large (480×480)',
        'medium' => 'Medium (220×220)',
        'thumbnail' => 'Thumbnail (100×100)',
      ],
      'media_type' => [
        'audio' => 'Audio',
        'document' => 'Document',
        'image' => 'Image',
        'remote_video' => 'Remote video',
        'video' => 'Video',
      ],
      'file' => [
        1 => 'Blue PNG',
        2 => 'green.jpg',
        3 => 'red.jpeg',
        6 => 'LICENSE.txt',
        7 => 'yellow.jpg',
        8 => 'video.webm',
        9 => 'video.mp4',
        10 => 'yellow.webp',
        11 => 'audio.m4a',
        12 => 'document.odt',
      ],
      'node' => [
        1 => 'Article with embed image media',
        2 => 'Article with only a single image',
      ],
      'node_type' => [
        'article' => 'Article',
        'page' => 'Basic page',
      ],
    ];

    // Drupal core 8.9.x prior 8.9.3 and Drupal core 9.0.x prior 9.0.3 were
    // unable to migrate "filtered_html" filter format due to a but in the
    // "subprocess" migrate process plugin.
    // @todo We can remove this after 8.9.3 and 9.0.3 are released.
    // @see https://drupal.org/i/3126063
    if (
      (version_compare(\Drupal::VERSION, '8.9.0', '>=') && version_compare(\Drupal::VERSION, '8.9.2', '<=')) ||
      (version_compare(\Drupal::VERSION, '9.0.0', '>=') && version_compare(\Drupal::VERSION, '9.0.2', '<='))
    ) {
      unset($expected_entities['filter_format']['filtered_html']);
    }

    // Drupal 9.1.x ships a new default image style "wide".
    if (version_compare(\Drupal::VERSION, '9.1', '>=')) {
      $expected_entities['image_style']['wide'] = 'Wide (1090)';
    }

    // PostgreSQL returns db records in different order, and this means that
    // also media entities are migrated in differnet order.
    $expected_entities['media'] = [
      1 => 'Blue PNG',
      2 => 'red.jpeg',
      3 => 'green.jpg',
      4 => 'yellow.jpg',
      5 => 'yellow.webp',
      6 => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
      7 => 'Responsive Images in Drupal 8',
      8 => 'video.webm',
      9 => 'video.mp4',
      10 => 'LICENSE.txt',
      11 => 'document.odt',
      12 => 'audio.m4a',
    ];
    if ($this->connectionIsPostgreSql()) {
      $expected_entities['media'] = [
        6 => 'video.webm',
        7 => 'video.mp4',
        8 => 'Responsive Images in Drupal 8',
        9 => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
        10 => 'audio.m4a',
        11 => 'LICENSE.txt',
        12 => 'document.odt',
      ] + $expected_entities['media'];
    }

    return $expected_entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    // This is not used.
    $entity_counts = [];

    foreach ($this->getExpectedEntities() as $entity_type_id => $expected_entities) {
      $entity_counts[$entity_type_id] = count($expected_entities);
    }

    return $entity_counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    // This is not used.
    return $this->getEntityCounts();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    $available_paths = [
      'block',
      'comment',
      'ctools',
      'dashboard',
      'dblog',
      'field',
      'field_sql_storage',
      'file',
      'filter',
      'image',
      'list',
      'menu',
      'node',
      'number',
      'options',
      'path',
      'rdf',
      'search',
      'shortcut',
      'system',
      'taxonomy',
      'text',
      'user',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database.
      'contextual',
      'field_ui',
      'help',
      'toolbar',
    ];

    // No idea why, but Drupal 9 threats available/missing migration paths
    // different than prior versions.
    if (version_compare(\Drupal::VERSION, '9.0', '<')) {
      $available_paths[] = 'file_entity';
    }

    // Drupal 9.1+ checks the human name of the modules.
    if (version_compare(\Drupal::VERSION, '9.1', '>=')) {
      $available_paths = [
        'Block',
        'Chaos tools',
        'Comment',
        'Contextual links',
        'Dashboard',
        'Database logging',
        'Field',
        'Field SQL storage',
        'Field UI',
        'File',
        'Filter',
        'Help',
        'Image',
        'List',
        'Menu',
        'Node',
        'Number',
        'Options',
        'Path',
        'Search',
        'Shortcut',
        'System',
        'Taxonomy',
        'Text',
        'Toolbar',
        'User',
      ];

      if (version_compare($this->coreMajorMinorVersion(), '9.5', '<')) {
        $available_paths[] = 'RDF';
      }
    }

    return $available_paths;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    $missing_paths = [
      'media',
      'media_internet',
      'media_vimeo',
      'media_wysiwyg',
      'media_youtube',
      'views',
      'wysiwyg',
    ];

    // No idea why, but Drupal 9 threats available/missing migration paths
    // different than prior versions.
    if (version_compare(\Drupal::VERSION, '9.0', '>=')) {
      $missing_paths[] = 'file_entity';
    }

    // Drupal 9.1+ checks the human name of the modules.
    if (version_compare(\Drupal::VERSION, '9.1', '>=')) {
      $missing_paths = [
        'Color',
        'File Entity',
        'Media',
        'Media Internet Sources',
        'Media WYSIWYG',
        'Media: Vimeo',
        'Media: YouTube',
        'Views',
        'Wysiwyg',
      ];
    }

    if (version_compare($this->coreMajorMinorVersion(), '9.5', '>=')) {
      $missing_paths[] = 'RDF';
    }

    return $missing_paths;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->destinationSiteVersion = explode('.', \Drupal::VERSION, 2)[0];

    // Delete 'article' content type. The destination Drupal 8|9 instance's
    // article content type will contain an image type field with the same name
    // that we have in the source Drupal 7 database. Media Migration tries to
    // change the field type of file and image fields to media reference, but
    // since the type of an existing field cannot be changed, this is the only
    // way to test the migration of media until we solve this issue.
    if ($node_type_storage = $this->getEntityStorage('node_type')) {
      if ($article_node_type = $node_type_storage->load('article')) {
        $article_node_type->delete();
      }
    }

    $this->loadFixture($this->getFixtureFilePath());
    $this->setEmbedTokenDestinationFilterPlugin($this->embedTokenDestinationFilterPlugin);
    $this->setEmbedMediaReferenceMethod($this->embedMediaReferenceMethod);

    // RDF module was deprecated in Drupal core 9.5.
    if (version_compare($this->coreMajorMinorVersion(), '9.5', '<')) {
      self::$modules[] = array_merge(self::$modules, [
        'rdf',
      ]);
    }
  }

  /**
   * Submits the Migrate Upgrade source connection and files form.
   */
  protected function submitMigrateUpgradeSourceConnectionForm() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->submitForm([], $this->t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = is_array($connection_options['prefix']) ? $connection_options['prefix']['default'] : $connection_options['prefix'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourcePrivateFilesPath(),
      'version' => $version,
      'source_base_path' => $this->getSourceBasePath(),
    ];

    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $edits = $this->translatePostValues($edit);

    $this->submitForm($edits, $this->t('Review upgrade'));
  }

  /**
   * Executes the upgrade process by the UI and asserts basic expectations.
   *
   * @param bool $assert_review_page
   *   Whether the review page should be tested or not. Defaults to TRUE.
   */
  protected function assertMigrateUpgradeViaUi(bool $assert_review_page = TRUE) {
    $this->submitMigrateUpgradeSourceConnectionForm();
    $session = $this->assertSession();
    $session->pageTextNotContains('Resolve all issues below to continue the upgrade.');

    // When complete node migration is executed, Drupal 8.9 and above (even 9.x)
    // will complain about content id conflicts. Drupal 8.8 and below won't.
    // @see https://www.drupal.org/node/2928118
    // @see https://www.drupal.org/node/3105503
    if (version_compare(\Drupal::VERSION, '8.9', '>=') && !Settings::get('migrate_node_migrate_type_classic', FALSE)) {
      $session->buttonExists($this->t('I acknowledge I may lose data. Continue anyway.'));
      $this->submitForm([], $this->t('I acknowledge I may lose data. Continue anyway.'));
      $session->statusCodeEquals(200);
    }

    if ($assert_review_page) {
      // Test the review page.
      $available_paths = $this->getAvailablePaths();
      $missing_paths = $this->getMissingPaths();
      $this->assertReviewPage($session, $available_paths, $missing_paths);
    }

    // Perform the upgrade.
    $this->submitForm([], $this->t('Perform upgrade'));
    $this->assertSession()->responseContains($this->t('Congratulations, you upgraded Drupal!'));

    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();
  }

  /**
   * Checks that migrations have been performed successfully.
   */
  protected function assertMediaMigrationResults() {
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);

    $this->assertEntities();

    $plugin_manager = $this->container->get('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);

    foreach ($all_migrations as $migration) {
      $id_map = $migration->getIdMap();
      foreach ($id_map as $source_id => $map) {
        // Convert $source_id into a keyless array so that
        // \Drupal\migrate\Plugin\migrate\id_map\Sql::getSourceHash() works as
        // expected.
        $source_id_values = array_values(unserialize($source_id));
        $row = $id_map->getRowBySource($source_id_values);
        $destination = serialize($id_map->currentDestination());
        $message = "Migration of $source_id to $destination as part of the {$migration->id()} migration. The source row status is " . $row['source_row_status'];
        // A completed migration should have maps with
        // MigrateIdMapInterface::STATUS_IGNORED or
        // MigrateIdMapInterface::STATUS_IMPORTED.
        $this->assertNotSame(MigrateIdMapInterface::STATUS_FAILED, $row['source_row_status'], $message);
        $this->assertNotSame(MigrateIdMapInterface::STATUS_NEEDS_UPDATE, $row['source_row_status'], $message);
      }
    }
  }

  /**
   * Pass if the page HTML title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the title is a different one.
   */
  protected function assertPageTitle($expected_title) {
    $page_title_element = $this->getSession()->getPage()->find('css', 'h1.page-title');
    if (!$page_title_element) {
      throw new ExpectationException('No page title element found on the page', $this->getSession()->getDriver());
    }
    $actual_title = $page_title_element->getText();
    $this->assertSame($expected_title, $actual_title, 'The page title is not the same as expected.');
  }

  /**
   * Asserts that the expected entities exist.
   */
  protected function assertEntities() {
    foreach ($this->getExpectedEntities() as $entity_type_id => $expected_entity_labels) {
      if ($storage = $this->getEntityStorage($entity_type_id)) {
        $ignored_entities = $this->getIgnoredEntities()[$entity_type_id] ?? [];
        $entities = $storage->loadMultiple();
        $actual_labels = array_reduce($entities, function ($carry, EntityInterface $entity) use ($ignored_entities) {
          $label = $entity->label();
          $id = $entity->id();
          if (
            empty($ignored_entities) ||
            (
              // If "$ignored_entities" contains a list of entity labels...
              array_search($label, $ignored_entities) === FALSE &&
              // If "$ignored_entities" contains a list of entity IDs.
              array_search($id, $ignored_entities) === FALSE
            )
          ) {
            $carry[$id] = $label;
          }
          return $carry;
        });
        $this->assertEquals($expected_entity_labels, $actual_labels, sprintf('The expected %s entities are not matching the actual ones.', $entity_type_id));
      }
      else {
        $this->fail(sprintf('The expected %s entity type is missing.', $entity_type_id));
      }
    }
  }

  /**
   * Helper method to assert the text on the 'Upgrade analysis report' page.
   *
   * This method is removed from Drupal core 9.1.x, but we need it for BC.
   *
   * @param \Drupal\Tests\WebAssert $session
   *   The web-assert session.
   * @param array $available_paths
   *   An array of modules that will be upgraded.
   * @param array $missing_paths
   *   An array of modules that will not be upgraded.
   */
  protected function assertReviewPage(WebAssert $session, array $available_paths = NULL, array $missing_paths = NULL) {
    if (is_callable('parent::assertReviewPage')) {
      parent::assertReviewPage($session, $available_paths, $missing_paths);
      return;
    }

    parent::assertReviewForm($available_paths, $missing_paths);
  }

  /**
   * Sets the method of the embed media reference.
   *
   * @param string|null $new_reference_method
   *   The reference method to set. This can be 'id', or 'uuid'.
   */
  protected function setEmbedMediaReferenceMethod($new_reference_method) {
    $current_method = Settings::get(MediaMigration::MEDIA_REFERENCE_METHOD_SETTINGS);

    if ($current_method !== $new_reference_method) {
      $settings['settings'][MediaMigration::MEDIA_REFERENCE_METHOD_SETTINGS] = (object) [
        'value' => $new_reference_method,
        'required' => TRUE,
      ];
      $this->writeSettings($settings);
    }
  }

  /**
   * Changes the entity embed token transform destination filter plugin.
   *
   * @param string|null $new_filter_plugin_id
   *   The new token transform destination plugin ID.
   */
  protected function setEmbedTokenDestinationFilterPlugin($new_filter_plugin_id) {
    $current_filter_plugin_id = Settings::get('media_migration_embed_token_transform_destination_filter_plugin');

    if ($new_filter_plugin_id !== $current_filter_plugin_id) {
      $settings['settings']['media_migration_embed_token_transform_destination_filter_plugin'] = (object) [
        'value' => $new_filter_plugin_id,
        'required' => TRUE,
      ];
      $this->writeSettings($settings);
    }
  }

  /**
   * Returns the specified entity's storage when the entity definition exists.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   *   The embed button's entity storage, or NULL if it does not exist.
   */
  protected function getEntityStorage(string $entity_type_id) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    try {
      $storage = $entity_type_manager->getStorage($entity_type_id);
    }
    catch (PluginNotFoundException $e) {
      // The entity type does not exist.
      return NULL;
    }

    return $storage;
  }

  /**
   * Sets the type of the node migration.
   *
   * @param bool $classic_node_migration
   *   Whether nodes should be migrated with the 'classic' way. If this is
   *   FALSE, and the current Drupal instance has the 'complete' migration, then
   *   the complete node migration will be used.
   */
  protected function setClassicNodeMigration(bool $classic_node_migration) {
    $current_method = Settings::get('migrate_node_migrate_type_classic', FALSE);

    if ($current_method !== $classic_node_migration) {
      $settings['settings']['migrate_node_migrate_type_classic'] = (object) [
        'value' => $classic_node_migration,
        'required' => TRUE,
      ];
      $this->writeSettings($settings);
    }
  }

  /**
   * Checks whether the actual DB connection is a PostgreSql connection.
   *
   * @return bool
   *   Whether the actual DB connection is a PostgreSql connection.
   */
  protected function connectionIsPostgreSql(): bool {
    return \Drupal::database()->getConnectionOptions()['driver'] === 'pgsql';
  }

}
