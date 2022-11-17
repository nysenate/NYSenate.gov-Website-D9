<?php

namespace Drupal\node_revision_delete;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * The Node Revision Delete service.
 *
 * @package Drupal\node_revision_delete
 */
class NodeRevisionDelete implements NodeRevisionDeleteInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected string $configurationFileName;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager
  ) {
    $this->configurationFileName = 'node_revision_delete.settings';
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function updateTimeMaxNumberConfig(string $config_name, int $max_number): void {
    // Looking for all the configured content types.
    $content_types = $this->getConfiguredContentTypes();
    // Checking the when_to_delete value for all the configured content types.
    foreach ($content_types as $content_type) {
      // Getting the config variables.
      $config = $this->configFactory->getEditable('node.type.' . $content_type->id());
      $third_party_settings = $config->get('third_party_settings');
      // If the new defined max_number is smaller than the defined
      // when_to_delete value in the config, we need to change the stored config
      // value.
      if (is_null($third_party_settings) || $max_number < $third_party_settings['node_revision_delete'][$config_name]) {
        $third_party_settings['node_revision_delete'][$config_name] = $max_number;
        // Saving the values in the config.
        $config->set('third_party_settings', $third_party_settings)->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguredContentTypes(): array {
    $configured_content_types = [];
    // Looking for all the content types.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    foreach ($content_types as $content_type) {
      // Getting the third_party_settings settings.
      $third_party_settings = $this->configFactory->get('node.type.' . $content_type->id())->get('third_party_settings');
      // Checking if the content type is configured.
      if (isset($third_party_settings['node_revision_delete'])) {
        $configured_content_types[] = $content_type;
      }
    }
    return $configured_content_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeString(string $config_name, int $number): string {
    // Getting the config.
    $config_name_time = $this->configFactory->get($this->configurationFileName)->get('node_revision_delete_' . $config_name . '_time');
    // Is singular or plural?
    $time = $this->getTimeNumberString($config_name_time['time']);
    // Return the time string for the $config_name parameter.
    $result = '';
    switch ($config_name) {
      case 'minimum_age_to_delete':
        $result = $number . ' ' . ($number == 1 ? $time['singular'] : $time['plural']);
        break;

      case 'when_to_delete':
        $result = $this->formatPlural($number, "After 1 {$time['singular']} of inactivity", "After @count {$time['plural']} of inactivity");
        break;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeNumberString(string $time): array {
    // Time options.
    $time_options = [
      'days' => [
        'singular' => $this->t('day'),
        'plural' => $this->t('days'),
      ],
      'weeks' => [
        'singular' => $this->t('week'),
        'plural' => $this->t('weeks'),
      ],
      'months' => [
        'singular' => $this->t('month'),
        'plural' => $this->t('months'),
      ],
    ];

    return $time_options[$time];
  }

  /**
   * {@inheritdoc}
   */
  public function saveContentTypeConfig(string $content_type, int $minimum_revisions_to_keep, int $minimum_age_to_delete, int $when_to_delete): void {
    // Getting the config file.
    $config = $this->configFactory->getEditable('node.type.' . $content_type);
    // Getting the variables with the content types configuration.
    $third_party_settings = $config->get('third_party_settings');
    // Adding the info into the array.
    $third_party_settings['node_revision_delete'] = [
      'minimum_revisions_to_keep' => $minimum_revisions_to_keep,
      'minimum_age_to_delete' => $minimum_age_to_delete,
      'when_to_delete' => $when_to_delete,
    ];
    // Saving the values in the config.
    $config->set('third_party_settings', $third_party_settings)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteContentTypeConfig(string $content_type): void {
    // Getting the config file.
    $config = $this->configFactory->getEditable('node.type.' . $content_type);
    // Getting the variables with the content types configuration.
    $third_party_settings = $config->get('third_party_settings');
    // Checking if the config exists.
    if (isset($third_party_settings['node_revision_delete'])) {
      // Deleting the value from the array.
      unset($third_party_settings['node_revision_delete']);
      // Saving the values in the config.
      $config->set('third_party_settings', $third_party_settings)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeValues(string $index = NULL) {

    $options_node_revision_delete_time = [
      '-1'       => $this->t('Never'),
      '0'        => $this->t('Every time cron runs'),
      '3600'     => $this->t('Every hour'),
      '86400'    => $this->t('Everyday'),
      '604800'   => $this->t('Every week'),
      '864000'   => $this->t('Every 10 days'),
      '1296000'  => $this->t('Every 15 days'),
      '2592000'  => $this->t('Every month'),
      '7776000'  => $this->t('Every 3 months'),
      '15552000' => $this->t('Every 6 months'),
      '31536000' => $this->t('Every year'),
      '63072000' => $this->t('Every 2 years'),
    ];

    if (isset($index) && isset($options_node_revision_delete_time[$index])) {
      return $options_node_revision_delete_time[$index];
    }
    else {
      return $options_node_revision_delete_time;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousRevisions(int $nid, int $currently_deleted_revision_id): array {
    // @todo check if the method can be improved.
    // Getting the node storage.
    $node_storage = $this->entityTypeManager->getStorage('node');
    // Getting the node.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    // Get current language code from URL.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Get all revisions of the current node, in all languages.
    $revision_ids = $node_storage->revisionIds($node);
    // Creating an array with the keys equal to the value.
    $revision_ids = array_combine($revision_ids, $revision_ids);

    // Adding a placeholder for the deleted revision, as our custom submit
    // function is executed after the core delete the current revision.
    $revision_ids[$currently_deleted_revision_id] = $currently_deleted_revision_id;

    $revisions_before = [];

    if (count($revision_ids) > 1) {
      // Ordering the array.
      krsort($revision_ids);

      // Getting the prior revisions.
      $revision_ids = array_slice($revision_ids, array_search($currently_deleted_revision_id, array_keys($revision_ids)) + 1, NULL, TRUE);

      // Loop through the list of revision ids, select the ones that have.
      // Same language as the current language AND are older than the current
      // deleted revision.
      foreach ($revision_ids as $vid) {
        /** @var \Drupal\Core\Entity\RevisionableInterface $revision */
        $revision = $node_storage->loadRevision($vid);
        // Only show revisions that are affected by the language
        // that is being displayed.
        if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $revisions_before[] = $revision;
        }
      }
    }

    return $revisions_before;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesRevisionsByNumber(int $number): array {
    if (!is_int($number) && $number < 0) {
      throw new \InvalidArgumentException("\$number parameter must be a positive integer");
    }

    // Looking for all the configured content types.
    $content_types = $this->getConfiguredContentTypes();

    $revisions = [];

    foreach ($content_types as $content_type) {
      // Getting the revisions.
      $revisions = array_merge($revisions, $this->getCandidatesRevisions($content_type->id(), $number));

      // Getting the number of revision we will delete.
      if ($number < count($revisions)) {
        $revisions = array_slice($revisions, 0, $number, TRUE);
        break;
      }
    }
    return $revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesRevisions(string $content_type, int $number = PHP_INT_MAX): array {
    // @todo check if the method can be improved.
    if (!is_int($number) && $number < 0) {
      throw new \InvalidArgumentException("\$number parameter must be a positive integer");
    }
    $candidate_revisions = [];

    // Getting the content type config.
    $content_type_config = $this->getContentTypeConfigWithRelativeTime($content_type);

    if (!empty($content_type_config)) {
      // Getting the candidate nodes.
      $candidate_nodes = $this->getCandidatesNodes($content_type);

      foreach ($candidate_nodes as $candidate_node) {
        $sub_query = $this->connection->select('node_field_data', 'n');
        $sub_query->join('node_revision', 'r', 'r.nid = n.nid');
        $sub_query->fields('r', ['vid', 'revision_timestamp']);
        $sub_query->condition('n.nid', $candidate_node);
        $sub_query->condition('changed', $content_type_config['when_to_delete'], '<');

        if ($this->configFactory->get($this->configurationFileName)->get('delete_newer')) {
          $sub_query->where('n.vid <> r.vid');
        }
        else {
          $sub_query->where('n.vid > r.vid');
        }

        $sub_query->groupBy('n.nid');
        $sub_query->groupBy('r.vid');
        $sub_query->groupBy('r.revision_timestamp');
        $sub_query->orderBy('revision_timestamp', 'DESC');
        // We need to reduce in 1 because we don't want to count the default
        // vid. We excluded the default revision in the where call.
        $sub_query->range($content_type_config['minimum_revisions_to_keep'] - 1, $number);

        // Allow other modules to alter candidates query.
        $sub_query->addTag('node_revision_delete_candidate_revisions');
        $sub_query->addTag('node_revision_delete_candidate_revisions_' . $content_type);

        $query = $this->connection->select($sub_query, 't');
        $query->fields('t', ['vid']);
        $query->condition('revision_timestamp', $content_type_config['minimum_age_to_delete'], '<');

        $candidate_revisions = array_merge($candidate_revisions, $query->execute()->fetchCol());
      }
    }

    return $candidate_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesRevisionsByNids(array $nids): array {
    // @todo check if the method can be improved.
    $candidate_revisions = [];
    // If we don't have nids returning an empty array.
    if (empty($nids)) {
      return $candidate_revisions;
    }
    // As all the nids must be of the same content type we just need to load
    // one.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load(current($nids));

    $content_type = $node->getType();

    // Getting the content type config.
    $content_type_config = $this->getContentTypeConfigWithRelativeTime($content_type);

    if (!empty($content_type_config)) {
      $sub_query = $this->connection->select('node_field_data', 'n');
      $sub_query->join('node_revision', 'r', 'r.nid = n.nid');
      $sub_query->fields('r', ['vid', 'revision_timestamp']);
      $sub_query->condition('n.nid', $nids, 'IN');
      $sub_query->condition('changed', $content_type_config['when_to_delete'], '<');

      if ($this->configFactory->get($this->configurationFileName)->get('delete_newer')) {
        $sub_query->where('n.vid <> r.vid');
      }
      else {
        $sub_query->where('n.vid > r.vid');
      }

      $sub_query->groupBy('n.nid');
      $sub_query->groupBy('r.vid');
      $sub_query->groupBy('revision_timestamp');
      $sub_query->orderBy('revision_timestamp', 'DESC');
      // We need to reduce in 1 because we don't want to count the default vid.
      // We excluded the default revision in the where call.
      $sub_query->range($content_type_config['minimum_revisions_to_keep'] - 1, PHP_INT_MAX);

      // Allow other modules to alter candidates query.
      $sub_query->addTag('node_revision_delete_candidate_revisions');
      $sub_query->addTag('node_revision_delete_candidate_revisions_' . $content_type);

      $query = $this->connection->select($sub_query, 't');
      $query->fields('t', ['vid']);
      $query->condition('revision_timestamp', $content_type_config['minimum_age_to_delete'], '<');

      $candidate_revisions = array_merge($candidate_revisions, $query->execute()
        ->fetchCol());
    }

    return $candidate_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeConfigWithRelativeTime(string $content_type): array {
    // Getting the content type config.
    $content_type_config = $this->getContentTypeConfig($content_type);

    if (!empty($content_type_config)) {
      // Getting the relative time for the minimum_age_to_delete.
      $content_type_config['minimum_age_to_delete'] = $this->getRelativeTime('minimum_age_to_delete', $content_type_config['minimum_age_to_delete']);
      // Getting the relative time for the when_to_delete.
      $content_type_config['when_to_delete'] = $this->getRelativeTime('when_to_delete', $content_type_config['when_to_delete']);
    }

    return $content_type_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeConfig(string $content_type): array {
    // Getting the variables with the content types configuration.
    $third_party_settings = $this->configFactory->get('node.type.' . $content_type)->get('third_party_settings');

    if (isset($third_party_settings['node_revision_delete'])) {
      return $third_party_settings['node_revision_delete'];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRelativeTime(string $config_name, int $number): int {
    // Getting the time interval.
    $time_interval = $this->configFactory->get($this->configurationFileName)->get('node_revision_delete_' . $config_name . '_time')['time'];
    // Getting the relative time.
    $time = strtotime('-' . $number . ' ' . $time_interval);

    return $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesNodes(string $content_type): array {
    // @todo check if the method can be improved.
    $result = [];
    // Getting the content type config.
    $content_type_config = $this->getContentTypeConfigWithRelativeTime($content_type);

    if (!empty($content_type_config)) {
      $query = $this->connection->select('node_field_data', 'n');
      $query->join('node_revision', 'r', 'r.nid = n.nid');
      $query->fields('n', ['nid']);
      $query->addExpression('COUNT(*)', 'total');
      $query->condition('type', $content_type);
      $query->condition('revision_timestamp', $content_type_config['minimum_age_to_delete'], '<');
      $query->condition('changed', $content_type_config['when_to_delete'], '<');
      $query->groupBy('n.nid');
      $query->having('COUNT(*) > ' . $content_type_config['minimum_revisions_to_keep']);

      // Allow other modules to alter candidates query.
      $query->addTag('node_revision_delete_candidates');
      $query->addTag('node_revision_delete_candidates_' . $content_type);

      $result = $query->execute()->fetchCol();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDeletionBatch(array $revisions, bool $dry_run): array {
    // Defining the batch builder.
    $batch_builder = new BatchBuilder();
    $batch_builder->setTitle($this->t('Deleting revisions'))
      ->setInitMessage($this->t('Starting to delete revisions.'))
      ->setProgressMessage($this->t('Deleted @current out of @total (@percentage%). Estimated time: @estimate.'))
      ->setErrorMessage($this->t('Error deleting revisions.'))
      ->setFinishCallback([NodeRevisionDeleteBatch::class, 'finish']);

    // Loop through the revisions to delete, create batch operations array.
    foreach ($revisions as $revision) {
      // Adding the operation.
      $batch_builder->addOperation(
        [NodeRevisionDeleteBatch::class, 'deleteRevision'],
        [$revision, $dry_run, count($revisions)]
      );
    }

    return $batch_builder->toArray();
  }

}
