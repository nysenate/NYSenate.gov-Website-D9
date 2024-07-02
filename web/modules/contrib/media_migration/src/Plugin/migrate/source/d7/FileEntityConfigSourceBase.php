<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\media_migration\FileEntityDealerManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for config migrate source plugins of file entity migrations.
 */
abstract class FileEntityConfigSourceBase extends ConfigSourceBase {

  /**
   * The file entity dealer plugin manager.
   *
   * @var \Drupal\media_migration\FileEntityDealerManagerInterface
   */
  protected $fileEntityDealerManager;

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
   * @param \Drupal\media_migration\FileEntityDealerManagerInterface $file_entity_dealer_manager
   *   The file entity dealer plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, FileEntityDealerManagerInterface $file_entity_dealer_manager) {
    $configuration += [
      'types' => NULL,
      'schemes' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->fileEntityDealerManager = $file_entity_dealer_manager;
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
      $container->get('plugin.manager.file_entity_dealer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'types' => $types,
      'schemes' => $schemes,
    ] = $this->configuration;

    $query = $this->getFileEntityBaseQuery();

    if ($types) {
      $query->condition('fm.type', explode(static::MULTIPLE_SEPARATOR, $types), 'IN');
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
    $this->query->addTag('migrate__media_migration__file_entity');
    $this->query->addTag('migrate__media_migration__media_configuration');
    $this->query->addTag("migrate__media_migration__source__{$this->pluginId}");

    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();
    $rows = [];
    foreach ($results as $result) {
      [
        'type' => $type,
        'scheme' => $scheme,
      ] = $result;

      if (!($dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme))) {
        continue;
      }

      $destination_media_type_id = $dealer_plugin->getDestinationMediaTypeId();
      $source_values = $rows[$destination_media_type_id] ?? $result + [
        'types' => $type,
        'schemes' => $scheme,
      ];

      $source_values['types'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['types']), [$type])));
      $source_values['schemes'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['schemes']), [$scheme])));
      $source_values['bundle'] = $destination_media_type_id;
      $source_values['bundle_label'] = $dealer_plugin->getDestinationMediaTypeLabel();
      $source_values['source_plugin_id'] = $dealer_plugin->getDestinationMediaSourcePluginId();
      $source_values['source_field_name'] = $dealer_plugin->getDestinationMediaSourceFieldName();
      $source_values['source_field_label'] = $dealer_plugin->getDestinationMediaTypeSourceFieldLabel();
      unset($source_values['type']);
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
      'types' => $this->t('File Entity type machine name'),
      'schemes' => $this->t('The uri scheme of the file entities'),
      'bundle' => 'bundle',
      'bundle_label' => 'bundle_label',
      'source_plugin_id' => $this->t('The source plugin id of the destination media type'),
      'source_field_name' => $this->t('The source field name of the destination media type'),
      'source_field_label' => 'source_field_label',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['types']['type'] = 'string';
    $ids['schemes']['type'] = 'string';
    return $ids;
  }

}
