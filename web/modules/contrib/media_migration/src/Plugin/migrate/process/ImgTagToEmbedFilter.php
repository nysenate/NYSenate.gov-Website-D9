<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\MediaMigrationUuidOracleInterface;
use Drupal\media_migration\Utility\MigrationPluginTool;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Masterminds\HTML5;
use Masterminds\HTML5\Parser\StringInputStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms <img src="/files/cat.png"> tags to <drupal-media …>.
 *
 * @MigrateProcessPlugin(
 *   id = "img_tag_to_embed"
 * )
 */
class ImgTagToEmbedFilter extends EmbedFilterBase {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The plugin ID of the filter which processes the embed code on destination.
   *
   * @var string
   */
  protected $destinationFilterPluginId;

  /**
   * Constructs a new ImgTagToEmbedFilter object.
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
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   * @param \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager|null $entity_embed_display_manager
   *   The entity embed display plugin manager service, if available.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migration lookup service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MediaMigrationUuidOracleInterface $media_uuid_oracle, LoggerChannelInterface $logger, $entity_embed_display_manager, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $media_uuid_oracle, $entity_embed_display_manager, $migrate_lookup, $entity_type_manager);

    $this->logger = $logger;
    $this->destinationFilterPluginId = MediaMigration::getEmbedTokenDestinationFilterPlugin();
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
      $container->get('logger.channel.media_migration'),
      $container->get('plugin.manager.entity_embed.display', ContainerInterface::NULL_ON_INVALID_REFERENCE),
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
    if (strpos($text, '<img ') === FALSE) {
      return $value;
    }

    if (!MediaMigration::embedTokenDestinationFilterPluginIsValid($this->destinationFilterPluginId)) {
      return $value;
    }

    $source_plugin = $this->migration->getSourcePlugin();
    if (!$source_plugin instanceof SqlBase) {
      return $value;
    }

    $probable_domain_names = $this->getProbableDomainNames($source_plugin->getDatabase());

    // Document why HTML5 instead of DomDocument.
    $html5 = new HTML5(['disable_html_ns' => TRUE]);

    // Compatibility for older HTML5 versions (e.g. in Drupal core 8.9.x).
    $dom_text = '<html><body>' . $text . '</body></html>';
    try {
      $dom = $html5->parse($dom_text);
    }
    catch (\TypeError $e) {
      $text_stream = new StringInputStream($dom_text);
      $dom = $html5->parse($text_stream);
    }

    $d7_file_public_path = $this->variableGet($source_plugin->getDatabase(), 'file_public_path', 'sites/default/files');
    $source_connection = $source_plugin->getDatabase();

    $images = $dom->getElementsByTagName('img');
    $images_count = $images->length;
    $skipped_images_count = 0;

    for ($i = 0; $i < $images_count; $i++) {
      $image = $images->item($skipped_images_count);
      $src = rawurldecode($image->getAttribute('src'));
      $url_parts = parse_url($src);
      $path = $url_parts['path'];

      // Support transforming absolute image URLs without knowing the source
      // site's domain name: validate that the correct public files path is
      // present in file URLs, and then look up the file by using the filename.
      if (strpos($path, '/' . $d7_file_public_path . '/') !== 0) {
        $skipped_images_count++;
        continue;
      }

      // Support transforming absolute image URLs without knowing the source
      // site's domain, but do not attempt to transform absolute URLs if we were
      // able to deduce probable domain names from watchdog log entries.
      if (isset($url_parts['host']) && !empty($probable_domain_names) && !in_array($url_parts['host'], $probable_domain_names)) {
        $skipped_images_count++;
        continue;
      }

      $escaped_file_path = preg_quote($d7_file_public_path, '/');
      $filesystem_location = preg_replace('/^\/' . $escaped_file_path . '\/(.*)$/', 'public://$1', $path);
      $file_id = FALSE;
      try {
        if ($source_connection->schema()->tableExists('file_managed')) {
          $file_id = $source_connection
            ->select('file_managed', 'fm')
            ->fields('fm', ['fid'])
            ->condition('fm.uri', $filesystem_location)
            ->execute()
            ->fetchField();
        }
      }
      catch (\Exception $e) {
      }

      if ($file_id === FALSE) {
        // If no file was found, distinguish between absolute URLs and relative
        // URLs. The latter are definitely errors on the source site. The former
        // may be hotlinking or not; this is impossible to know without knowing
        // the source site's domain(s).
        $row_source_id_string = preg_replace(
          '/\s+/',
          ' ',
          Variable::export($row->getSourceIdValues())
        );

        if (strpos($src, 'http') === 0 || strpos($src, '//') === 0) {
          $this->logger->log(RfcLogLevel::INFO, sprintf("No file found for the absolute image URL in tag '%s' used in the '%s' migration's source row with source ID %s while processing the destination property '%s'.", $html5->saveHTML($image), $this->migration->id(), $row_source_id_string, $destination_property));
        }
        else {
          $this->logger->log(RfcLogLevel::WARNING, sprintf("No file found for the relative image URL in tag '%s' used in the '%s' migration's source row with source ID %s while processing the destination property '%s'.", $html5->saveHTML($image), $this->migration->id(), $row_source_id_string, $destination_property));
        }

        $skipped_images_count++;
        continue;
      }

      // Delete the consumed attribute.
      $image->removeAttribute('src');

      // Generate the <drupal-media> tag that will replace the <img> tag.
      $replacement_node = $this->createEmbedNode($dom, $file_id);

      // Best-effort support for data-align.
      // @see \Drupal\filter\Plugin\Filter\FilterAlign
      // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Img#attr-align
      if ($image->hasAttribute('align')) {
        $replacement_node->setAttribute('data-align', $image->getAttribute('align'));
        // Delete the consumed attribute.
        $image->removeAttribute('align');
      }
      if ($image->hasAttribute('style')) {
        $styles = explode(';', $image->getAttribute('style'));
        foreach ($styles as $index => $style) {
          // We have to get the last value of a float style property definition,
          // so we must not have a break here, after the first match.
          if (preg_match('/;float\s*\:\s*(left|right);/', ';' . trim($style) . ';', $matches)) {
            $replacement_node->setAttribute('data-align', $matches[1]);
            unset($styles[$index]);
            $image->setAttribute('style', implode(';', $styles));
          }
        }
      }

      // Best-effort support for data-caption.
      // @see \Drupal\filter\Plugin\Filter\FilterCaption
      // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/figcaption
      $target_node = $image;
      if ($image->parentNode->tagName === 'figure') {
        $target_node = $image->parentNode;
        foreach ($image->parentNode->childNodes as $child) {
          if ($child instanceof \DOMElement && $child->tagName === 'figcaption') {
            $caption_html = $html5->saveHTML($child->childNodes);
            $replacement_node->setAttribute('data-caption', $caption_html);
            break;
          }
        }
      }

      // Retain all other attributes. Currently the media_embed filter
      // explicitly supports the `alt` and `title` attributes, but it may
      // support more attributes in the future. We avoid data loss and allow
      // contrib modules to add more filtering.
      // @see \Drupal\media\Plugin\Filter\MediaEmbed::applyPerEmbedMediaOverrides()
      foreach ($image->attributes as $attribute) {
        if ($attribute->name === 'style' && empty($attribute->value)) {
          continue;
        }
        $replacement_node->setAttribute($attribute->name, $attribute->value);
      }

      $target_node->parentNode->insertBefore($replacement_node, $target_node);
      $target_node->parentNode->removeChild($target_node);
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

  /**
   * Reads a variable from a source Drupal database.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The source database connection.
   * @param string $name
   *   Name of the variable.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The unserialized value of the Drupal 7 variable, of the given default.
   */
  protected function variableGet(Connection $connection, string $name, $default) {
    try {
      $result = $connection->select('variable', 'v')
        ->fields('v', ['value'])
        ->condition('name', $name)
        ->execute()
        ->fetchField();
    }
    // The table might not exist.
    catch (\Exception $e) {
      $result = FALSE;
    }
    return $result !== FALSE ? unserialize($result) : $default;
  }

  /**
   * Gets the probable domain names by inspecting the watchdog table.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The source database connection.
   *
   * @return string[]
   *   The probable domain names.
   */
  protected function getProbableDomainNames(Connection $connection) : array {
    try {
      $query = $connection->select('watchdog', 'w');
      $query->addExpression('DISTINCT (SUBSTR(SUBSTR(location, INSTR(location, \'//\') + 2), 1, INSTR(SUBSTR(location, INSTR(location, \'//\') + 2), \'/\') - 1))');
      $result = $query->execute()
        ->fetchAll();
    }
    // The table might not exist.
    catch (\Exception $e) {
      return [];
    }
    $domain_names = [];
    foreach ($result as $row) {
      $domain_names[] = $row->expression;
    }
    return $domain_names;
  }

  /**
   * Creates a DOM element representing an embed media on the destination.
   *
   * @param \DOMDocument $dom
   *   The \DOMDocument in which the embed \DOMElement is being created.
   * @param string|int $file_id
   *   The ID of the file which should be represented by the new embed tag.
   *
   * @return \DOMElement
   *   The new embed tag as a writable \DOMElement.
   */
  protected function createEmbedNode(\DOMDocument $dom, $file_id) {
    $filter_destination_is_entity_embed = $this->destinationFilterPluginId === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED;
    $tag = $filter_destination_is_entity_embed ?
      'drupal-entity' :
      'drupal-media';
    $display_mode_attribute = $filter_destination_is_entity_embed ?
      'data-entity-embed-display' :
      'data-view-mode';

    $embed_node = $dom->createElement($tag);
    $embed_node->setAttribute('data-entity-type', 'media');
    $migrations = $this->configuration['migrations'] ?? MigrationPluginTool::getMediaEntityMigrationIds();
    if (MediaMigration::getEmbedMediaReferenceMethod() === MediaMigration::EMBED_MEDIA_REFERENCE_METHOD_ID) {
      $destination_id = $this->getMigratedMediaId($file_id, $migrations);
      $embed_node->setAttribute('data-entity-id', $destination_id);
    }
    else {
      $uuid = $this->getExistingMediaUuid($file_id, $migrations) ??
        $this->mediaUuidOracle->getMediaUuid((int) $file_id);
      $embed_node->setAttribute('data-entity-uuid', $uuid);
    }
    $embed_node->setAttribute($display_mode_attribute, 'default');
    if ($filter_destination_is_entity_embed) {
      $embed_node->setAttribute('data-embed-button', 'media');
    }
    $embed_node->setAttribute($display_mode_attribute, $this->getDisplayPluginId('default', $this->destinationFilterPluginId));

    return $embed_node;
  }

}
