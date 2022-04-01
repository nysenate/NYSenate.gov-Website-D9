<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\simple_sitemap\Queue\QueueWorker;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Datetime\Time;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase;

/**
 * Class Simplesitemap
 * @package Drupal\simple_sitemap
 */
class Simplesitemap {

  /**
   * @var \Drupal\simple_sitemap\EntityHelper
   */
  protected $entityHelper;

  /**
   * @var \Drupal\simple_sitemap\SimplesitemapSettings
   */
  protected $settings;

  /**
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * @var \Drupal\simple_sitemap\Queue\QueueWorker
   */
  protected $queueWorker;

  /**
   * @var array
   */
  protected $variants;

  /**
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * @var array
   */
  protected static $allowedLinkSettings = [
    'entity' => ['index', 'priority', 'changefreq', 'include_images'],
    'custom' => ['priority', 'changefreq'],
  ];

  /**
   * @var array
   */
  protected static $linkSettingDefaults = [
    'index' => FALSE,
    'priority' => '0.5',
    'changefreq' => '',
    'include_images' => FALSE,
  ];

  /**
   * Simplesitemap constructor.
   * @param \Drupal\simple_sitemap\EntityHelper $entity_helper
   * @param \Drupal\simple_sitemap\SimplesitemapSettings $settings
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Path\PathValidator $path_validator
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   * @param \Drupal\Component\Datetime\Time $time
   * @param \Drupal\simple_sitemap\Queue\QueueWorker $queue_worker
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Drupal\simple_sitemap\Logger $logger
   */
  public function __construct(
    EntityHelper $entity_helper,
    SimplesitemapSettings $settings,
    SimplesitemapManager $manager,
    ConfigFactory $config_factory,
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    PathValidator $path_validator,
    DateFormatter $date_formatter,
    Time $time,
    QueueWorker $queue_worker,
    LockBackendInterface $lock = NULL,
    Logger $logger = NULL
  ) {
    $this->entityHelper = $entity_helper;
    $this->settings = $settings;
    $this->manager = $manager;
    $this->configFactory = $config_factory;
    $this->db = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->pathValidator = $path_validator;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->queueWorker = $queue_worker;
    if ($lock === NULL) {
      @trigger_error('Calling Simplesitemap::__construct() without the $lock argument is deprecated in simple_sitemap:3.9. The $lock argument will be required in simple_sitemap:3.10.', E_USER_DEPRECATED);
      $lock = \Drupal::service('lock');
    }
    $this->lock = $lock;
    if ($logger === NULL) {
      @trigger_error('Calling Simplesitemap::__construct() without the $logger argument is deprecated in simple_sitemap:3.9. The $logger argument will be required in simple_sitemap:3.10.', E_USER_DEPRECATED);
      $logger = \Drupal::service('simple_sitemap.logger');
    }
    $this->logger = $logger;
  }

  /**
   * Returns a specific sitemap setting or a default value if setting does not
   * exist.
   *
   * @param string $name
   *  Name of the setting, like 'max_links'.
   *
   * @param mixed $default
   *  Value to be returned if the setting does not exist in the configuration.
   *
   * @return mixed
   *  The current setting from configuration or a default value.
   */
  public function getSetting($name, $default = FALSE) {
    return $this->settings->getSetting($name, $default);
  }

  /**
   * Stores a specific sitemap setting in configuration.
   *
   * @param string $name
   *  Setting name, like 'max_links'.
   *
   * @param mixed $setting
   *  The setting to be saved.
   *
   * @return $this
   */
  public function saveSetting($name, $setting) {
    $this->settings->saveSetting($name, $setting);

    return $this;
  }

  /**
   * @return \Drupal\simple_sitemap\Queue\QueueWorker
   */
  public function getQueueWorker() {
    return $this->queueWorker;
  }

  /**
   * @return \Drupal\simple_sitemap\SimplesitemapManager
   */
  public function getSitemapManager() {
    return $this->manager;
  }

  /**
   * @param array|string|true|null $variants
   *  array: Array of variants to be set.
   *  string: A particular variant to be set.
   *  null: Default variant will be set.
   *  true: All existing variants will be set.
   *
   * @return $this
   *
   * @todo Check if variants exist and throw exception.
   */
  public function setVariants($variants = NULL) {
    if (NULL === $variants) {
      $this->variants = !empty($default_variant = $this->getSetting('default_variant', ''))
        ? [$default_variant]
        : [];
    }
    elseif ($variants === TRUE) {
      $this->variants = array_keys(
        $this->manager->getSitemapVariants(NULL, FALSE));
    }
    else {
      $this->variants = (array) $variants;
    }

    return $this;
  }

  /**
   * Gets the currently set variants, the default variant, or all variants.
   *
   * @param bool $default_get_all
   *  If true and no variants are set, all variants are returned. If false and
   *  no variants are set, only the default variant is returned.
   *
   * @return array
   */
  protected function getVariants($default_get_all = TRUE) {
    if (NULL === $this->variants) {
      $this->setVariants($default_get_all ? TRUE : NULL);
    }

    return $this->variants;
  }

  /**
   * Returns a sitemap variant, its index, or its requested chunk.
   *
   * @param int|null $delta
   *  Optional delta of the chunk.
   *
   * @return string|false
   *  If no chunk delta is provided, either the sitemap variant is returned,
   *  or its index in case of a chunked sitemap.
   *  If a chunk delta is provided, the relevant chunk is returned.
   *  Returns false if the sitemap variant is not retrievable from the database.
   */
  public function getSitemap($delta = NULL) {
    $chunk_info = $this->fetchSitemapVariantInfo();

    if (empty($delta) || !isset($chunk_info[$delta])) {

      if (isset($chunk_info[SitemapGeneratorBase::INDEX_DELTA])) {
        // Return sitemap index if one exists.
        return $this->fetchSitemapChunk($chunk_info[SitemapGeneratorBase::INDEX_DELTA]->id)
          ->sitemap_string;
      }

      // Return sitemap chunk if there is only one chunk.
      return isset($chunk_info[SitemapGeneratorBase::FIRST_CHUNK_DELTA])
        ? $this->fetchSitemapChunk($chunk_info[SitemapGeneratorBase::FIRST_CHUNK_DELTA]->id)
          ->sitemap_string
        : FALSE;
    }

    // Return specific sitemap chunk.
    return $this->fetchSitemapChunk($chunk_info[$delta]->id)->sitemap_string;
  }

  /**
   * Fetches info about all published sitemap variants and their chunks.
   *
   * @return array
   *  An array containing all published sitemap chunk IDs, deltas and creation
   *  timestamps keyed by the currently set variants, or in case of only one
   *  variant set the above keyed by sitemap delta.
   */
  protected function fetchSitemapVariantInfo() {
    if (!empty($this->getVariants())) {
      $result = $this->db->select('simple_sitemap', 's')
        ->fields('s', ['id', 'delta', 'sitemap_created', 'type'])
        ->condition('s.status', 1)
        ->condition('s.type', $this->getVariants(), 'IN')
        ->execute();

      return count($this->getVariants()) > 1
        ? $result->fetchAllAssoc('type')
        : $result->fetchAllAssoc('delta');
    }

    return [];
  }

  /**
   * Fetches a single sitemap chunk by ID.
   *
   * @param int $id
   *   The chunk ID.
   *
   * @return object
   *   A sitemap chunk object.
   */
  protected function fetchSitemapChunk($id) {
    return $this->db->query('SELECT * FROM {simple_sitemap} WHERE id = :id',
      [':id' => $id])->fetchObject();
  }

  /**
   * Removes sitemap instances for the currently set variants.
   *
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function removeSitemap() {
    $this->manager->removeSitemap($this->getVariants(FALSE));

    return $this;
  }

  /**
   * Generates all sitemaps.
   *
   * @param string $from
   *  Can be 'form', 'drush', 'cron' and 'backend'.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function generateSitemap($from = QueueWorker::GENERATE_TYPE_FORM) {
    if (!$this->lock->lockMayBeAvailable(QueueWorker::LOCK_ID)) {
      $this->logger->m('Unable to acquire a lock for sitemap generation.')->log('error')->display('error');
      return $this;
    }
    switch ($from) {
      case QueueWorker::GENERATE_TYPE_FORM:
      case QueueWorker::GENERATE_TYPE_DRUSH;
        $this->queueWorker->batchGenerateSitemap($from);
        break;

      case QueueWorker::GENERATE_TYPE_CRON:
      case QueueWorker::GENERATE_TYPE_BACKEND:
        $this->queueWorker->generateSitemap($from);
        break;
    }

    return $this;
  }

  /**
   * Queues links from currently set variants.
   *
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function queue() {
    $this->queueWorker->queue($this->getVariants());

    return $this;
  }

  /**
   * Deletes the queue and queues links from currently set variants.
   *
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue() {
    if (!$this->lock->lockMayBeAvailable(QueueWorker::LOCK_ID)) {
      $this->logger->m('Unable to acquire a lock for sitemap generation.')->log('error')->display('error');
      return $this;
    }
    $this->queueWorker->rebuildQueue($this->getVariants());

    return $this;
  }

  /**
   * Returns a 'time ago' string of last timestamp generation.
   *
   * @param string|null $variant
   *
   * @return string|array|false
   *  Formatted timestamp of last sitemap generation, otherwise FALSE.
   */
/*  public function getGeneratedAgo() {
    $chunks = $this->fetchSitemapVariantInfo();
    return isset($chunks[DefaultSitemapGenerator::FIRST_CHUNK_DELTA]->sitemap_created)
      ? $this->dateFormatter
        ->formatInterval($this->time->getRequestTime() - $chunks[DefaultSitemapGenerator::FIRST_CHUNK_DELTA]
            ->sitemap_created)
      : FALSE;
  }*/

  /**
   * Enables sitemap support for an entity type. Enabled entity types show
   * sitemap settings on their bundle setting forms. If an enabled entity type
   * features bundles (e.g. 'node'), it needs to be set up with
   * setBundleSettings() as well.
   *
   * @param string $entity_type_id
   *  Entity type id like 'node'.
   *
   * @return $this
   */
  public function enableEntityType($entity_type_id) {
    $enabled_entity_types = $this->getSetting('enabled_entity_types');
    if (!in_array($entity_type_id, $enabled_entity_types)) {
      $enabled_entity_types[] = $entity_type_id;
      $this->saveSetting('enabled_entity_types', $enabled_entity_types);
    }

    return $this;
  }

  /**
   * Disables sitemap support for an entity type. Disabling support for an
   * entity type deletes its sitemap settings permanently and removes sitemap
   * settings from entity forms.
   *
   * @param string $entity_type_id
   *
   * @return $this
   */
  public function disableEntityType($entity_type_id) {

    // Updating settings.
    $enabled_entity_types = $this->getSetting('enabled_entity_types');
    if (FALSE !== ($key = array_search($entity_type_id, $enabled_entity_types))) {
      unset ($enabled_entity_types[$key]);
      $this->saveSetting('enabled_entity_types', array_values($enabled_entity_types));
    }

    // Deleting inclusion settings.
    $config_names = $this->configFactory->listAll('simple_sitemap.bundle_settings.');
    foreach ($config_names as $config_name) {
      $config_name_parts = explode('.', $config_name);
      if ($config_name_parts[3] === $entity_type_id) {
        $this->configFactory->getEditable($config_name)->delete();
      }
    }

    // Deleting entity overrides.
    $this->setVariants(TRUE)->removeEntityInstanceSettings($entity_type_id);

    return $this;
  }

  /**
   * Sets settings for bundle or non-bundle entity types. This is done for the
   * currently set variant.
   *
   * Note that this method takes only the first set variant into account. See todo.
   *
   * @param $entity_type_id
   * @param null $bundle_name
   * @param array $settings
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo multiple variants
   */
  public function setBundleSettings($entity_type_id, $bundle_name = NULL, $settings = ['index' => TRUE]) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return $this;
    }

    $bundle_name = NULL !== $bundle_name ? $bundle_name : $entity_type_id;

    if (!empty($old_settings = $this->getBundleSettings($entity_type_id, $bundle_name))) {
      $settings = array_merge($old_settings, $settings);
    }
    self::supplementDefaultSettings('entity', $settings);

    if ($settings != $old_settings) {

      // Save new bundle settings to configuration.
      $bundle_settings = $this->configFactory
        ->getEditable("simple_sitemap.bundle_settings.$variants[0].$entity_type_id.$bundle_name");
      foreach ($settings as $setting_key => $setting) {
        $bundle_settings->set($setting_key, $setting);
      }
      $bundle_settings->save();

      if (empty($entity_ids = $this->entityHelper->getEntityInstanceIds($entity_type_id, $bundle_name))) {
        return $this;
      }

      // Delete all entity overrides in case bundle indexation is disabled.
      if (empty($settings['index'])) {
        $this->removeEntityInstanceSettings($entity_type_id, $entity_ids);

        return $this;
      }

      // Delete entity overrides which are identical to new bundle settings.
      // todo Enclose into some sensible method.
      $query = $this->db->select('simple_sitemap_entity_overrides', 'o')
        ->fields('o', ['id', 'inclusion_settings'])
        ->condition('o.entity_type', $entity_type_id)
        ->condition('o.type', $variants[0]);
      if (!empty($entity_ids)) {
        $query->condition('o.entity_id', $entity_ids, 'IN');
      }

      $delete_instances = [];
      foreach ($query->execute()->fetchAll() as $result) {
        $delete = TRUE;
        $instance_settings = unserialize($result->inclusion_settings);
        foreach ($instance_settings as $setting_key => $instance_setting) {
          if ($instance_setting != $settings[$setting_key]) {
            $delete = FALSE;
            break;
          }
        }
        if ($delete) {
          $delete_instances[] = $result->id;
        }
      }
      if (!empty($delete_instances)) {

        // todo Use removeEntityInstanceSettings() instead.
        $this->db->delete('simple_sitemap_entity_overrides')
          ->condition('id', $delete_instances, 'IN')
          ->execute();
      }
    }

    return $this;
  }

  /**
   * Gets settings for bundle or non-bundle entity types. This is done for the
   * currently set variants.
   *
   * @param string|null $entity_type_id
   *  Limit the result set to a specific entity type.
   *
   * @param string|null $bundle_name
   *  Limit the result set to a specific bundle name.
   *
   * @param bool $supplement_defaults
   *  Supplements the result set with default bundle settings.
   *
   * @param bool $multiple_variants
   *  If true, returns an array of results keyed by variant name, otherwise it
   *  returns the result set for the first variant only.
   *
   * @return array|false
   *  Array of settings or array of settings keyed by variant name. False if
   *  entity type does not exist.
   */
  public function getBundleSettings($entity_type_id = NULL, $bundle_name = NULL, $supplement_defaults = TRUE, $multiple_variants = FALSE) {
    $bundle_name = NULL !== $bundle_name ? $bundle_name : $entity_type_id;
    $all_bundle_settings = [];

    foreach ($variants = $this->getVariants(FALSE) as $variant) {
      if (NULL !== $entity_type_id) {
        $bundle_settings = $this->configFactory
          ->get("simple_sitemap.bundle_settings.$variant.$entity_type_id.$bundle_name")
          ->get();

        if (empty($bundle_settings) && $supplement_defaults) {
          self::supplementDefaultSettings('entity', $bundle_settings);
        }
      }
      else {
        $config_names = $this->configFactory->listAll("simple_sitemap.bundle_settings.$variant.");
        $bundle_settings = [];
        foreach ($config_names as $config_name) {
          $config_name_parts = explode('.', $config_name);
          $bundle_settings[$config_name_parts[3]][$config_name_parts[4]] = $this->configFactory->get($config_name)->get();
        }

        // Supplement default bundle settings for all bundles not found in simple_sitemap.bundle_settings.*.* configuration.
        if ($supplement_defaults) {
          foreach ($this->entityHelper->getSupportedEntityTypes() as $type_id => $type_definition) {
            foreach($this->entityHelper->getBundleInfo($type_id) as $bundle => $bundle_definition) {
              if (!isset($bundle_settings[$type_id][$bundle])) {
                self::supplementDefaultSettings('entity', $bundle_settings[$type_id][$bundle]);
              }
            }
          }
        }
      }

      if ($multiple_variants) {
        $all_bundle_settings[$variant] = $bundle_settings;
      }
      else {
        return $bundle_settings;
      }
    }

    return $all_bundle_settings;
  }

  /**
   * Removes settings for bundle or a non-bundle entity types. This is done for
   * the currently set variants.
   *
   * @param string|null $entity_type_id
   *  Limit the removal to a specific entity type.
   *
   * @param string|null $bundle_name
   *  Limit the removal to a specific bundle name.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function removeBundleSettings($entity_type_id = NULL, $bundle_name = NULL) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return $this;
    }

    if (NULL !== $entity_type_id) {
      $bundle_name = NULL !== $bundle_name ? $bundle_name : $entity_type_id;

      foreach ($variants as $variant) {
        $this->configFactory
          ->getEditable("simple_sitemap.bundle_settings.$variant.$entity_type_id.$bundle_name")->delete();
      }

      if (!empty($entity_ids = $this->entityHelper->getEntityInstanceIds($entity_type_id, $bundle_name))) {
        $this->removeEntityInstanceSettings($entity_type_id, $entity_ids);
      }
    }
    else {
      foreach ($variants as $variant) {
        $config_names = $this->configFactory->listAll("simple_sitemap.bundle_settings.$variant.");
        foreach ($config_names as $config_name) {
          $this->configFactory->getEditable($config_name)->delete();
        }
      }
      $this->removeEntityInstanceSettings();
    }

    return $this;
  }

  /**
   * Supplements all missing link setting with default values.
   *
   * @param string $type
   *  Can be 'entity' or 'custom'.
   *
   * @param array &$settings
   * @param array $overrides
   */
  public static function supplementDefaultSettings($type, &$settings, $overrides = []) {
    foreach (self::$allowedLinkSettings[$type] as $allowed_link_setting) {
      if (!isset($settings[$allowed_link_setting])
        && isset(self::$linkSettingDefaults[$allowed_link_setting])) {
        $settings[$allowed_link_setting] = isset($overrides[$allowed_link_setting])
          ? $overrides[$allowed_link_setting]
          : self::$linkSettingDefaults[$allowed_link_setting];
      }
    }
  }

  /**
   * Overrides sitemap settings for a single entity for the currently set
   * variants.
   *
   * @param string $entity_type_id
   * @param string $id
   * @param array $settings
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setEntityInstanceSettings($entity_type_id, $id, $settings) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return $this;
    }

    if (empty($entity = $this->entityTypeManager->getStorage($entity_type_id)->load($id))) {
      // todo exception
      return $this;
    }

    $all_bundle_settings = $this->getBundleSettings(
      $entity_type_id, $this->entityHelper->getEntityInstanceBundleName($entity), TRUE, TRUE
    );

    foreach ($all_bundle_settings as $variant => $bundle_settings) {
      if (!empty($bundle_settings)) {

        // Check if overrides are different from bundle setting before saving.
        $override = FALSE;
        foreach ($settings as $key => $setting) {
          if (!isset($bundle_settings[$key]) || $setting != $bundle_settings[$key]) {
            $override = TRUE;
            break;
          }
        }

        // Save overrides for this entity if something is different.
        if ($override) {
          $this->db->merge('simple_sitemap_entity_overrides')
            ->keys([
              'type' => $variant,
              'entity_type' => $entity_type_id,
              'entity_id' => $id])
            ->fields([
              'type' => $variant,
              'entity_type' => $entity_type_id,
              'entity_id' => $id,
              'inclusion_settings' => serialize(array_merge($bundle_settings, $settings))])
            ->execute();
        }
        // Else unset override.
        else {
          $this->removeEntityInstanceSettings($entity_type_id, $id);
        }
      }
    }

    return $this;
  }

  /**
   * Gets sitemap settings for an entity instance which overrides bundle
   * settings, or gets bundle settings, if they are not overridden. This is
   * done for the currently set variant.
   * Please note, this method takes only the first set
   * variant into account. See todo.
   *
   * @param string $entity_type_id
   * @param string $id
   *
   * @return array|false
   *  Array of entity instance settings or the settings of its bundle. False if
   *  entity type or variant does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo multiple variants
   * @todo: May want to use Simplesitemap::supplementDefaultSettings('entity', $settings) inside here instead of calling it everywhere this method is called.
   */
  public function getEntityInstanceSettings($entity_type_id, $id) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return FALSE;
    }

    $results = $this->db->select('simple_sitemap_entity_overrides', 'o')
      ->fields('o', ['inclusion_settings'])
      ->condition('o.type', $variants[0])
      ->condition('o.entity_type', $entity_type_id)
      ->condition('o.entity_id', $id)
      ->execute()
      ->fetchField();

    if (!empty($results)) {
      return unserialize($results);
    }

    if (empty($entity = $this->entityTypeManager->getStorage($entity_type_id)->load($id))) {
      return FALSE;
    }

    return $this->getBundleSettings(
      $entity_type_id,
      $this->entityHelper->getEntityInstanceBundleName($entity)
    );
  }

  /**
   * Removes sitemap settings for entities that override bundle settings. This
   * is done for the currently set variants.
   *
   * @param string|null $entity_type_id
   *  Limits the removal to a certain entity type.
   *
   * @param string|null $entity_ids
   *  Limits the removal to entities with certain IDs.
   *
   * @return $this
   */
  public function removeEntityInstanceSettings($entity_type_id = NULL, $entity_ids = NULL) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return $this;
    }

    $query = $this->db->delete('simple_sitemap_entity_overrides')
      ->condition('type', $variants, 'IN');

    if (NULL !== $entity_type_id) {
      $query->condition('entity_type', $entity_type_id);

      if (NULL !== $entity_ids) {
        $query->condition('entity_id', (array) $entity_ids, 'IN');
      }
    }

    $query->execute();

    return $this;
  }

  /**
   * Checks if an entity bundle (or a non-bundle entity type) is set to be
   * indexed for any of the currently set variants.
   *
   * @param string $entity_type_id
   * @param string|null $bundle_name
   *
   * @return bool
   */
  public function bundleIsIndexed($entity_type_id, $bundle_name = NULL) {
    foreach ($this->getBundleSettings($entity_type_id, $bundle_name, FALSE, TRUE) as $settings) {
      if (!empty($settings['index'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if an entity type is enabled in the sitemap settings.
   *
   * @param string $entity_type_id
   *
   * @return bool
   */
  public function entityTypeIsEnabled($entity_type_id) {
    return in_array($entity_type_id, $this->getSetting('enabled_entity_types', []));
  }

  /**
   * Stores a custom path along with its settings to configuration for the
   * currently set variants.
   *
   * @param string $path
   *
   * @param array $settings
   *  Settings that are not provided are supplemented by defaults.
   *
   * @return $this
   *
   * @todo Validate $settings and throw exceptions
   */
  public function addCustomLink($path, $settings = []) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return $this;
    }

    if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
      // todo: log error.
      return $this;
    }
    if ($path[0] !== '/') {
      // todo: log error.
      return $this;
    }

    $variant_links = $this->getCustomLinks(NULL, FALSE, TRUE);
    foreach ($variants as $variant) {
      $links = [];
      $link_key = 0;
      if (isset($variant_links[$variant])) {
        $links = $variant_links[$variant];
        $link_key = count($links);
        foreach ($links as $key => $link) {
          if ($link['path'] === $path) {
            $link_key = $key;
            break;
          }
        }
      }

      $links[$link_key] = ['path' => $path] + $settings;
      $this->configFactory->getEditable("simple_sitemap.custom_links.$variant")
        ->set('links', $links)->save();
    }

    return $this;
  }

  /**
   * Gets custom link settings for the currently set variants.
   *
   * @param string|null $path
   *  Limits the result set by an internal path.
   *
   * @param bool $supplement_defaults
   *  Supplements the result set with default custom link settings.
   *
   * @param bool $multiple_variants
   *  If true, returns an array of results keyed by variant name, otherwise it
   *  returns the result set for the first variant only.
   *
   * @return array|mixed|null
   */
  public function getCustomLinks($path = NULL, $supplement_defaults = TRUE, $multiple_variants = FALSE) {
    $all_custom_links = [];
    foreach ($variants = $this->getVariants(FALSE) as $variant) {
      $custom_links = $this->configFactory
        ->get("simple_sitemap.custom_links.$variant")
        ->get('links');

      $custom_links = !empty($custom_links) ? $custom_links : [];

      if (!empty($custom_links) && $path !== NULL) {
        foreach ($custom_links as $key => $link) {
          if ($link['path'] !== $path) {
            unset($custom_links[$key]);
          }
        }
      }

      if (!empty($custom_links) && $supplement_defaults) {
        foreach ($custom_links as $i => $link_settings) {
          self::supplementDefaultSettings('custom', $link_settings);
          $custom_links[$i] = $link_settings;
        }
      }

      $custom_links = $path !== NULL && !empty($custom_links)
        ? array_values($custom_links)[0]
        : array_values($custom_links);


      if (!empty($custom_links)) {
        if ($multiple_variants) {
          $all_custom_links[$variant] = $custom_links;
        }
        else {
          return $custom_links;
        }
      }
    }

    return $all_custom_links;
  }

  /**
   * Removes custom links from currently set variants.
   *
   * @param array|null $paths
   *  Limits the removal to certain paths.
   *
   * @return $this
   */
  public function removeCustomLinks($paths = NULL) {
    if (empty($variants = $this->getVariants(FALSE))) {
      return $this;
    }

    if (NULL === $paths) {
      foreach ($variants as $variant) {
        $this->configFactory
          ->getEditable("simple_sitemap.custom_links.$variant")->delete();
      }
    }
    else {
      $variant_links = $this->getCustomLinks(NULL, FALSE, TRUE);
      foreach ($variant_links as $variant => $links) {
        $custom_links = $links;
        $save = FALSE;
        foreach ((array) $paths  as $path) {
          foreach ($custom_links as $key => $link) {
            if ($link['path'] === $path) {
              unset($custom_links[$key]);
              $save = TRUE;
              break 2;
            }
          }
        }
        if ($save) {
          $this->configFactory->getEditable("simple_sitemap.custom_links.$variant")
            ->set('links', array_values($custom_links))->save();
        }
      }
    }

    return $this;
  }
}
