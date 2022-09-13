<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\media_migration\FileDealerManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for config migrate source plugins of plain file migrations.
 */
abstract class FilePlainConfigSourceBase extends ConfigSourceBase {

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
    $configuration += [
      'mimes' => NULL,
      'schemes' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->fileDealerManager = $file_dealer_manager;
    $this->sourceHasFileEntities = $this->getDatabase()->schema()->fieldExists('file_managed', 'type');
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
      'mimes' => $mimes,
      'schemes' => $schemes,
    ] = $this->configuration;
    $query = $this->getFilePlainBaseQuery();

    if ($this->sourceHasFileEntities) {
      $query->condition('fm.type', ['undefined', ''], 'IN');
    }

    if ($mimes) {
      $query->where("{$this->getMainMimeTypeExpression()} IN (:mimes[])", [
        ':mimes[]' => explode(static::MULTIPLE_SEPARATOR, $mimes),
      ]);
    }

    if ($schemes) {
      $query->where("{$this->getSchemeExpression()} IN (:schemes[])", [
        ':schemes[]' => explode(static::MULTIPLE_SEPARATOR, $schemes),
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
    $this->query->addTag('migrate__media_migration__media_configuration');
    $this->query->addTag("migrate__media_migration__source__{$this->pluginId}");

    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();

    // Add the array of all instances using the same media type to each row.
    $rows = [];
    foreach ($results as $result) {
      [
        'mime' => $mime,
        'scheme' => $scheme,
      ] = $result;

      if (!($dealer_plugin = $this->fileDealerManager->createInstanceFromSchemeAndMime($scheme, $mime))) {
        continue;
      }

      $destination_media_type_id = $dealer_plugin->getDestinationMediaTypeId();
      $source_values = $rows[$destination_media_type_id] ?? $result + [
        'mimes' => $mime,
        'schemes' => $scheme,
      ];

      $source_values['mimes'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['mimes']), [$mime])));
      $source_values['schemes'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['schemes']), [$scheme])));
      $source_values['bundle'] = $destination_media_type_id;
      $source_values['bundle_label'] = $dealer_plugin->getDestinationMediaTypeLabel();
      $source_values['source_plugin_id'] = $dealer_plugin->getDestinationMediaSourcePluginId();
      $source_values['source_field_name'] = $dealer_plugin->getDestinationMediaSourceFieldName();
      $source_values['source_field_label'] = $dealer_plugin->getDestinationMediaTypeSourceFieldLabel();
      unset($source_values['mime']);
      unset($source_values['scheme']);
      $rows[$destination_media_type_id] = $source_values;
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'mimes' => $this->t('List of the files main MIME type (before the slash), separated by "::"'),
      'schemes' => $this->t('List of the files uri scheme (the ID of the stream wrapper), separated by "::"'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mimes']['type'] = 'string';
    $ids['schemes']['type'] = 'string';
    return $ids;
  }

}
