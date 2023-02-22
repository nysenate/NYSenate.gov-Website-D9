<?php

namespace Drupal\entity_usage;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation for track plugins.
 */
abstract class EntityUsageTrackBase extends PluginBase implements EntityUsageTrackInterface, ContainerFactoryPluginInterface {

  /**
   * The usage tracking service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $usageService;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Update config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The EntityRepository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;
 
  /**
   * The Drupal Path Validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The public file directory.
   *
   * @var string
   */
  protected $publicFileDirectory;

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\entity_usage\EntityUsageInterface $usage_service
   *   The usage tracking service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The EntityRepositoryInterface service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The Drupal Path Validator service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperInterface $public_stream
   *   The Public Stream service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityUsageInterface $usage_service, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, EntityRepositoryInterface $entity_repository, PathValidatorInterface $path_validator, StreamWrapperInterface $public_stream) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    $this->usageService = $usage_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->config = $config_factory->get('entity_usage.settings');
    $this->entityRepository = $entity_repository;
    $this->pathValidator = $path_validator;
    $this->publicFileDirectory = $public_stream->getDirectoryPath();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_usage.usage'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('entity.repository'),
      $container->get('path.validator'),
      $container->get('stream_wrapper.public')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'] ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableFieldTypes() {
    return $this->pluginDefinition['field_types'] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function trackOnEntityCreation(EntityInterface $source_entity) {
    $trackable_field_types = $this->getApplicableFieldTypes();
    $fields = array_keys($this->getReferencingFields($source_entity, $trackable_field_types));
    foreach ($fields as $field_name) {
      if ($source_entity->hasField($field_name) && !$source_entity->{$field_name}->isEmpty()) {
        /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
        foreach ($source_entity->{$field_name} as $field_item) {
          // The entity is being created with value on this field, so we just
          // need to add a tracking record.
          $target_entities = $this->getTargetEntities($field_item);
          foreach ($target_entities as $target_entity) {
            [$target_type, $target_id] = explode("|", $target_entity);
            $source_vid = ($source_entity instanceof RevisionableInterface && $source_entity->getRevisionId()) ? $source_entity->getRevisionId() : 0;
            $this->usageService->registerUsage($target_id, $target_type, $source_entity->id(), $source_entity->getEntityTypeId(), $source_entity->language()->getId(), $source_vid, $this->pluginId, $field_name);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackOnEntityUpdate(EntityInterface $source_entity) {
    // We depend on $source_entity->original to do anything useful here.
    if (empty($source_entity->original)) {
      return;
    }
    $trackable_field_types = $this->getApplicableFieldTypes();
    $fields = array_keys($this->getReferencingFields($source_entity, $trackable_field_types));
    foreach ($fields as $field_name) {
      if (($source_entity instanceof RevisionableInterface) &&
        $source_entity->getRevisionId() != $source_entity->original->getRevisionId() &&
        $source_entity->hasField($field_name) &&
        !$source_entity->{$field_name}->isEmpty()) {

        $this->trackOnEntityCreation($source_entity);
        return;
      }

      // We are updating an existing revision, compare target entities to see if
      // we need to add or remove tracking records.
      $current_targets = [];
      if ($source_entity->hasField($field_name) && !$source_entity->{$field_name}->isEmpty()) {
        foreach ($source_entity->{$field_name} as $field_item) {
          $target_entities = $this->getTargetEntities($field_item);
          foreach ($target_entities as $target_entity) {
            $current_targets[] = $target_entity;
          }
        }
      }

      $original_targets = [];
      if ($source_entity->original->hasField($field_name) && !$source_entity->original->{$field_name}->isEmpty()) {
        foreach ($source_entity->original->{$field_name} as $field_item) {
          $target_entities = $this->getTargetEntities($field_item);
          foreach ($target_entities as $target_entity) {
            $original_targets[] = $target_entity;
          }
        }
      }

      // If a field references the same target entity, we record only one usage.
      $original_targets = array_unique($original_targets);
      $current_targets = array_unique($current_targets);

      $added_ids = array_diff($current_targets, $original_targets);
      $removed_ids = array_diff($original_targets, $current_targets);

      foreach ($added_ids as $added_entity) {
        [$target_type, $target_id] = explode('|', $added_entity);
        $source_vid = ($source_entity instanceof RevisionableInterface && $source_entity->getRevisionId()) ? $source_entity->getRevisionId() : 0;
        $this->usageService->registerUsage($target_id, $target_type, $source_entity->id(), $source_entity->getEntityTypeId(), $source_entity->language()->getId(), $source_vid, $this->pluginId, $field_name);
      }
      foreach ($removed_ids as $removed_entity) {
        [$target_type, $target_id] = explode('|', $removed_entity);
        $source_vid = ($source_entity instanceof RevisionableInterface && $source_entity->getRevisionId()) ? $source_entity->getRevisionId() : 0;
        $this->usageService->registerUsage($target_id, $target_type, $source_entity->id(), $source_entity->getEntityTypeId(), $source_entity->language()->getId(), $source_vid, $this->pluginId, $field_name, 0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencingFields(EntityInterface $source_entity, array $field_types) {
    $referencing_fields_on_bundle = [];
    if (!($source_entity instanceof FieldableEntityInterface)) {
      return $referencing_fields_on_bundle;
    }

    $source_entity_type_id = $source_entity->getEntityTypeId();
    $all_fields_on_bundle = $this->entityFieldManager->getFieldDefinitions($source_entity_type_id, $source_entity->bundle());
    foreach ($all_fields_on_bundle as $field_name => $field) {
      if (in_array($field->getType(), $field_types)) {
        $referencing_fields_on_bundle[$field_name] = $field;
      }
    }

    if (!$this->config->get('track_enabled_base_fields')) {
      foreach ($referencing_fields_on_bundle as $key => $referencing_field_on_bundle) {
        if ($referencing_field_on_bundle->getFieldStorageDefinition()->isBaseField()) {
          unset($referencing_fields_on_bundle[$key]);
        }
      }
    }

    return $referencing_fields_on_bundle;
  }
 
  /**
   * Process the url to a Url object.
   *
   * @param string $url
   *   A relative or absolute URL string.
   *
   * @return \Drupal\Core\Url|false
   *   The Url object
   */
  protected function processUrl($url) {
    // Strip off the scheme and host, so we only get the path.
    $site_domains = $this->config->get('site_domains') ?: [];
    foreach ($site_domains as $site_domain) {
      $site_domain = rtrim($site_domain, "/");
      $host_pattern = str_replace('.', '\.', $site_domain) . "/";
      $host_pattern = "/" . str_replace("/", '\/', $host_pattern) . "/";
      if (preg_match($host_pattern, $url)) {
        // Strip off everything that is not the internal path.
        $url = parse_url($url, PHP_URL_PATH);

        if (preg_match('/^[^\/]+(\/.+)/', $site_domain, $matches)) {
          $sub_directory = $matches[1];
          if ($sub_directory && substr($url, 0, strlen($sub_directory)) == $sub_directory) {
            $url = substr($url, strlen($sub_directory));
          }
        }

        break;
      }
    }

    return $this->pathValidator()->getUrlIfValidWithoutAccessCheck($url);
  }

  /**
   * Try to retrieve an entity from an URL string.
   *
   * @param string $url
   *   A relative or absolute URL string.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object that corresponds to the received URL, or NULL if no
   *   entity could be retrieved.
   */
  protected function findEntityByUrlString($url) {
    if (empty($url)) {
      return NULL;
    }

    $entity = NULL;

    $url_object = $this->processUrl($url);

    $public_file_pattern = '{^/?' . $this->publicFileDirectory() . '/}';

    if ($url_object && $url_object->isRouted()) {
      $entity = $this->findEntityByRoutedUrl($url_object);
    }
    elseif (preg_match($public_file_pattern, $url)) {
      // Check if we can map the link to a public file.
      $file_uri = preg_replace($public_file_pattern, 'public://', urldecode($url));
      $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $file_uri]);
      if ($files) {
        // File entity found.
        $target_type = 'file';
        $target_id = array_keys($files)[0];

        if ($target_type && $target_id) {
          $entity = $this->entityTypeManager->getStorage($target_type)->load($target_id);
        }
      }
    }

    return $entity;
  }

  /**
   * Try to retrieve an entity from an URL object.
   *
   * @param \Drupal\Core\Url $url
   *   A URL object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object that corresponds to the URL object, or NULL if no
   *   entity could be retrieved.
   */
  protected function findEntityByRoutedUrl(Url $url) {
    if (!$url || !$url->isRouted()) {
      return NULL;
    }

    $entity = NULL;
    $target_type = NULL;
    $target_id = NULL;

    $entity_pattern = '/^entity\.([a-z_]*)\./';

    if (preg_match($entity_pattern, $url->getRouteName(), $matches)) {
      // Ge the target entity type and ID.
      if ($target_entity_type = $this->entityTypeManager->getDefinition($matches[1])) {
        $route_parameters = $url->getRouteParameters();
        $target_type = $target_entity_type->id();
        $target_id = $route_parameters[$target_type];
      }
    }

    if ($target_type && $target_id) {
      $entity = $this->entityTypeManager->getStorage($target_type)->load($target_id);
    }

    return $entity;
  }

  /**
   * Returns the path validator service.
   *
   * @return \Drupal\Core\Path\PathValidatorInterface
   *   The path validator.
   */
  protected function pathValidator() {
    return $this->pathValidator;
  }

  /**
   * Return the public file directory path.
   *
   * @return string
   *   The public file directory path.
   */
  protected function publicFileDirectory() {
    if (!$this->publicFileDirectory) {
      $this->publicFileDirectory = \Drupal::service('stream_wrapper.public')->getDirectoryPath();
    }
    return $this->publicFileDirectory;
  }

}
