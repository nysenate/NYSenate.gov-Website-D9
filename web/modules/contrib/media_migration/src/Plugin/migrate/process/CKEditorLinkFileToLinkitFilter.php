<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\MediaMigrationUuidOracleInterface;
use Drupal\media_migration\Traits\MediaLookupTrait;
use Drupal\media_migration\Utility\MigrationPluginTool;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Masterminds\HTML5;
use Masterminds\HTML5\Parser\StringInputStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms <a href="/file/%"> tags to <a data-entity-substitution="media" …>.
 *
 * @MigrateProcessPlugin(
 *   id = "ckeditor_link_file_to_linkit"
 * )
 */
class CKEditorLinkFileToLinkitFilter extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  use MediaLookupTrait;

  /**
   * The migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The media UUID oracle.
   *
   * @var \Drupal\media_migration\MediaMigrationUuidOracleInterface
   */
  protected $mediaUuidOracle;

  /**
   * Constructs a new CKEditorLinkFileToLinkitFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\media_migration\MediaMigrationUuidOracleInterface $media_uuid_oracle
   *   The media UUID oracle.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migration lookup service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MediaMigrationUuidOracleInterface $media_uuid_oracle, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->migration = $migration;
    $this->mediaUuidOracle = $media_uuid_oracle;
    $this->migrateLookup = $migrate_lookup;
    $this->mediaStorage = $entity_type_manager->getStorage('media');
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
      $container->get('media_migration.media_uuid_oracle'),
      $container->get('migrate.lookup'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value_is_array = is_array($value);
    $text = (string) ($value_is_array ? $value['value'] : $value);
    if (!preg_match(MediaMigration::PCRE_PATTERN_LINKIT_FILE_LINK, $text)) {
      return $value;
    }

    $html5 = new HTML5(['disable_html_ns' => TRUE]);

    // Compatibility for older HTML5 versions (e.g. in Drupal core 8.9.x).
    try {
      $dom = $html5->parse('<html><body>' . $text . '</body></html>');
    }
    catch (\TypeError $e) {
      $text_stream = new StringInputStream('<html><body>' . $text . '</body></html>');
      $dom = $html5->parse($text_stream);
    }

    foreach ($dom->getElementsByTagName('a') as $node) {
      /** @var \DOMElement $node */
      $src = rawurldecode($node->getAttribute('href'));
      $url_parts = parse_url($src);
      $path = $url_parts['path'] ?? '';

      if (strpos($path, '/file/') !== 0) {
        continue;
      }

      $file_entity_id = basename($path);
      if (!preg_match('/^\d+$/', $file_entity_id)) {
        continue;
      }
      $migrations = $this->configuration['migrations'] ?? MigrationPluginTool::getMediaEntityMigrationIds();
      $media_uuid = $this->getExistingMediaUuid($file_entity_id, $migrations) ??
        $this->mediaUuidOracle->getMediaUuid((int) $file_entity_id);

      // Add the additional attributes to allow the linkit filter to work.
      $node->setAttribute('data-entity-substitution', 'media');
      $node->setAttribute('data-entity-type', 'media');
      $node->setAttribute('data-entity-uuid', $media_uuid);
    }

    $result = $html5->saveHTML($dom->documentElement->firstChild->childNodes);
    if ($value_is_array) {
      $value['value'] = $result;
    }
    else {
      $value = $result;
    }
    return $value;
  }

}
