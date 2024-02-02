<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\file\Entity\File;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\MediaMigrationUuidOracleInterface;
use Drupal\media_migration\Utility\MigrationPluginTool;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Processes [[{"type":"media","fid":"1234",...}]] tokens in content.
 *
 * These style tokens come from media_wysiwyg module. The regex it uses to match
 * them for reference is:
 *
 * /\[\[.+?"type":"media".+?\]\]/s
 *
 * @code
 * # From this
 * [[{"type":"media","fid":"1234",...}]]
 *
 * # To this
 * <drupal-entity
 *   data-embed-button="media"
 *   data-entity-embed-display="view_mode:media.full"
 *   data-entity-type="media"
 *   data-entity-id="1234"></drupal-entity>
 * # or to this:
 * <drupal-media
 *   data-entity-type="media"
 *   data-view-mode="full"
 *   data-entity-uuid="12345678-9abc-def0-1234-56789abcdef0"></drupal-media>
 * @endcode
 *
 * Usage:
 *
 * @endcode
 * process:
 *   bar:
 *     plugin: media_wysiwyg_filter
 *     view_mode_matching:
 *       default: full
 *     media_migrations:
 *      - upgrade_d7_file_entity_archive
 *      - upgrade_d7_file_entity_image
 *      - upgrade_d7_file_entity_publication
 *     file_migrations:
 *      - upgrade_d7_file
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "media_wysiwyg_filter"
 * )
 */
class MediaWysiwygFilter extends EmbedFilterBase implements ConfigurableInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new MediaWysiwygFilter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager|null $entity_embed_display_manager
   *   The entity embed display plugin manager service, if available.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\media_migration\MediaMigrationUuidOracleInterface $media_uuid_oracle
   *   The media UUID oracle.
   *   The entity embed display plugin manager service, if available.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migration lookup service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, $entity_embed_display_manager, MigrationPluginManagerInterface $migration_plugin_manager, MediaMigrationUuidOracleInterface $media_uuid_oracle, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $media_uuid_oracle, $entity_embed_display_manager, $migrate_lookup, $entity_type_manager);
    $this->setConfiguration($configuration);

    $this->migrationPluginManager = $migration_plugin_manager;
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
      $container->get('plugin.manager.entity_embed.display', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('plugin.manager.migration'),
      $container->get('media_migration.media_uuid_oracle'),
      $container->get('migrate.lookup'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode_matching' => [],
      'media_migrations' => NULL,
      'file_migrations' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Merge in defaults.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!MediaMigration::embedTokenDestinationFilterPluginIsValid(MediaMigration::getEmbedTokenDestinationFilterPlugin())) {
      throw new MigrateException("The embed token's destination filter plugin ID is invalid.");
    }

    $pattern = '/\[\[\s*(?<tag_info>\{.+\})\s*\]\]/sU';
    if (defined(JsonDecode::class . '::ASSOCIATIVE')) {
      $decoder = new JsonDecode([JsonDecode::ASSOCIATIVE => TRUE]);
    }
    else {
      $decoder = new JsonDecode(TRUE);
    }
    $entity_type_id = explode(':', $this->migration->getDestinationConfiguration()['plugin'])[1];
    $source_identifier = [];
    foreach ($row->getSourceIdValues() as $source_id_key => $source_id_value) {
      $source_identifier[] = "$source_id_key $source_id_value";
    }
    $source_identifier = implode(', ', $source_identifier);

    $value_is_array = is_array($value);
    $text = (string) ($value_is_array ? $value['value'] : $value);

    $text = preg_replace_callback($pattern, function ($matches) use ($decoder, $entity_type_id, $source_identifier) {
      // Replace line breaks with a single space for valid JSON.
      $matches['tag_info'] = preg_replace('/\s+/', ' ', $matches['tag_info']);

      try {
        $tag_info = $decoder->decode($matches['tag_info'], JsonEncoder::FORMAT);

        if (!is_array($tag_info) || !array_key_exists('fid', $tag_info)) {
          return $matches[0];
        }

        // Find matching view mode.
        if ($this->configuration['view_mode_matching']) {
          foreach ($this->configuration['view_mode_matching'] as $key => $match) {
            if ($key == $tag_info['view_mode'] ?? NULL) {
              $tag_info['view_mode'] = $match;
            }
          }
        }

        $embed_metadata = [
          'id' => $tag_info['fid'],
          'view_mode' => $tag_info['view_mode'] ?? 'default',
        ];

        $source_attributes = !empty($tag_info['attributes']) ?
          $tag_info['attributes'] : [];

        // Add alt and title overrides.
        foreach (['alt', 'title'] as $attribute_name) {
          if (!empty($source_attributes[$attribute_name])) {
            $embed_metadata[$attribute_name] = $source_attributes[$attribute_name];
          }
        }

        // Add alignment.
        if (!empty($source_attributes['class']) && is_string($source_attributes['class'])) {
          $alignment_map = [
            'media-wysiwyg-align-center' => 'center',
            'media-wysiwyg-align-left' => 'left',
            'media-wysiwyg-align-right' => 'right',
          ];
          $classes_array = array_unique(explode(' ', preg_replace('/\s{2,}/', ' ', trim($source_attributes['class']))));

          foreach ($alignment_map as $original => $replacement) {
            if (in_array($original, $classes_array, TRUE)) {
              $embed_metadata['data-align'] = $replacement;
              break;
            }
          }
        }

        return $this->getEmbedCode($embed_metadata) ?? $matches[0];
      }
      catch (NotEncodableValueException $e) {
        // There was an error decoding the JSON.
        $this->messenger()->addWarning(sprintf('The following media_wysiwyg token in %s %s does not have valid JSON: %s', $entity_type_id, $source_identifier, $matches[0]));
        return $matches[0];
      }
      catch (\LogicException $e) {
        return $matches[0];
      }
    }, $text);

    // Update fid and token in regex /file/{fid}/download?token={token}.
    if ($this->configuration['file_migrations']) {
      $pattern = '#\/file\/([0-9]*)\/download\?token=([a-zA-Z0-9]*)#';
      $replacement_template = '/file/%s/download?token=%s';
      $text = preg_replace_callback($pattern, function ($matches) use ($replacement_template) {
        $oldId = $matches[1];
        $newId = $this->findDestId($oldId, $this->configuration['file_migrations']);
        $newToken = '';

        try {
          $reflector = new \ReflectionClass('Drupal\file_entity\Entity\FileEntity');

          if ($reflector->hasMethod('getDownloadToken') && $file = File::load($newId)) {
            $newToken = $file->getDownloadToken();
          }
        }
        catch (\ReflectionException $e) {
        }

        return sprintf($replacement_template, $newId, $newToken);
      }, $text);
    }

    if ($value_is_array) {
      $value['value'] = $text;
    }
    else {
      $value = $text;
    }
    return $value;
  }

  /**
   * Find new ID using the migration lookup system.
   *
   * @param int $source_id
   *   The original ID.
   * @param array $migrations
   *   The ID of the migrations to look at.
   *
   * @return int
   *   The new ID.
   */
  protected function findDestId($source_id, array $migrations) {
    return $this->getMigratedMediaId($source_id, $migrations) ?? $source_id;
  }

  /**
   * Creates the replacement token for the specified embed filter.
   */
  protected function getEmbedCode(array $embed_metadata) {
    if (empty($embed_metadata['id']) || empty($embed_metadata['view_mode'])) {
      return NULL;
    }
    $destination_filter_plugin_id = MediaMigration::getEmbedTokenDestinationFilterPlugin();
    $embed_media_reference_method = MediaMigration::getEmbedMediaReferenceMethod();
    $filter_destination_is_entity_embed = $destination_filter_plugin_id === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED;
    $reference_method_is_id = $embed_media_reference_method === MediaMigration::EMBED_MEDIA_REFERENCE_METHOD_ID;
    $tag = $filter_destination_is_entity_embed ?
      'drupal-entity' :
      'drupal-media';

    // Add the static attributes first.
    $attributes = $filter_destination_is_entity_embed ?
      ['data-embed-button' => 'media'] :
      [];
    $attributes['data-entity-type'] = 'media';

    // Add the attribute that references the embed media.
    $reference_attribute = $reference_method_is_id ?
      'data-entity-id' :
      'data-entity-uuid';

    $migrations = $this->configuration['media_migrations'] ?? MigrationPluginTool::getMediaEntityMigrationIds();

    $attributes[$reference_attribute] = $reference_method_is_id
      ? $this->findDestId($embed_metadata['id'], $migrations)
      : $this->getExistingMediaUuid($embed_metadata['id'], $migrations) ?? $this->mediaUuidOracle->getMediaUuid((int) $embed_metadata['id']);
    $embed_metadata['id'] = $this->findDestId($embed_metadata['id'], $migrations);

    // Add attribute that controls how the embed media is displayed.
    $display_mode_property = $destination_filter_plugin_id === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED ?
      'data-entity-embed-display' :
      'data-view-mode';
    $attributes[$display_mode_property] = $this->getDisplayPluginId($embed_metadata['view_mode'], $destination_filter_plugin_id);

    // Alt, title, caption and align should be handled conditionally.
    $conditional_attributes = ['alt', 'title', 'data-caption', 'data-align'];
    foreach ($conditional_attributes as $conditional_attribute) {
      if (!empty($embed_metadata[$conditional_attribute])) {
        $attributes[$conditional_attribute] = $embed_metadata[$conditional_attribute];
      }
    }

    $attribute = new Attribute($attributes);
    return "<$tag{$attribute->__toString()}></$tag>";
  }

}
