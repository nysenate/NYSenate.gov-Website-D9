<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\media_migration\FileDealerManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File Plain source plugin.
 *
 * Available configuration keys:
 * - type: (optional) If supplied, this will only return fields
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_file_plain",
 *   source_module = "file",
 * )
 */
class FilePlain extends FieldableEntity implements ContainerFactoryPluginInterface {

  use MediaMigrationDatabaseTrait;

  /**
   * Whether the source has (fieldable) file entities or not.
   *
   * @var bool
   */
  protected $sourceHasFileEntities;

  /**
   * The file dealer plugin manager.
   *
   * @var \Drupal\media_migration\FileDealerManagerInterface
   */
  protected $fileDealerManager;

  /**
   * Constructs a plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_migration\FileDealerManagerInterface $file_dealer_manager
   *   The file entity dealer plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, FileDealerManagerInterface $file_dealer_manager) {
    $configuration += ['mime' => NULL, 'scheme' => NULL];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    $this->sourceHasFileEntities = $this->getDatabase()->schema()->fieldExists('file_managed', 'type');
    $this->fileDealerManager = $file_dealer_manager;
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
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.file_dealer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'mime' => $mime,
      'scheme' => $scheme,
    ] = $this->configuration;

    $query = $this->getFilePlainBaseQuery(NULL, FALSE);
    $query->fields('fm');
    $query->orderBy('fm.timestamp', 'ASC');

    if ($this->sourceHasFileEntities) {
      $query->condition('fm.type', ['undefined', ''], 'IN');
    }

    if ($mime) {
      $query->where("{$this->getMainMimeTypeExpression()} = :mime", [
        ':mime' => $mime,
      ]);
    }

    if ($scheme) {
      $query->where("{$this->getSchemeExpression()} = :scheme", [
        ':scheme' => $scheme,
      ]);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    parent::prepareQuery();

    $this->query->addTag('migrate__media_migration');
    $this->query->addTag('migrate__media_migration__file_plain');
    $this->query->addTag('migrate__media_migration__media_content');
    $this->query->addTag("migrate__media_migration__source__{$this->pluginId}");

    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'mime' => $mime,
      'scheme' => $scheme,
    ] = $row->getSource();

    if (!($dealer_plugin = $this->fileDealerManager->createInstanceFromSchemeAndMime($scheme, $mime))) {
      return FALSE;
    }

    $row->setSourceProperty('bundle', $dealer_plugin->getDestinationMediaTypeId());
    $dealer_plugin->prepareMediaEntityRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Fields provided by file_admin module are only included here for developer
    // convenience so that they can be adjusted by altering the generated
    // migration plugins.
    $fields = [
      'fid' => $this->t('The file identifier'),
      'source_field_type' => $this->t('The type of the field where the file is referenced.'),
      'uid' => $this->t('The user identifier'),
      'filename' => $this->t('The file name'),
      'uri' => $this->t('The URI of the file'),
      'filemime' => $this->t('The file mimetype'),
      'filesize' => $this->t('The file size'),
      'status' => $this->t('The file status'),
      'timestamp' => $this->t('The time that the file was added'),
      'created' => $this->t('The created timestamp - (if file_admin module is present in Drupal 7)'),
      'published' => $this->t('The published timestamp - (if file_admin module is present in Drupal 7)'),
      'promote' => $this->t('The promoted flag - (if file_admin module is present in Drupal 7)'),
      'sticky' => $this->t('The sticky flag - (if file_admin module is present in Drupal 7)'),
      'vid' => $this->t('The vid'),
      'alt' => $this->t('The alternate text for the image (if this is a value of an image field)'),
      'title' => $this->t('The title text for the image (if this is a value of an image field)'),
      'mime' => $this->t('The main MIME type of the file'),
      'scheme' => $this->t('The uri scheme of the file (the ID of the stream wrapper)'),
      'description' => $this->t('The file description of the image (if this is a value of a file field)'),
      'display' => $this->t('The display value of the image (if this is a value of a file field)'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['fid']['alias'] = 'fm';
    return $ids;
  }

}
