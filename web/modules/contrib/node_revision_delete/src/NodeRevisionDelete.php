<?php

namespace Drupal\node_revision_delete;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager;

/**
 * The Node Revision Delete service.
 *
 * @package Drupal\node_revision_delete
 */
class NodeRevisionDelete implements NodeRevisionDeleteInterface {

  use StringTranslationTrait;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The Node Revision Plugin Manager.
   *
   * @var \Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager
   */
  protected NodeRevisionDeletePluginManager $pluginManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager $node_revision_plugin_manager
   *   Node revision plugin manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    Connection $connection,
    NodeRevisionDeletePluginManager $node_revision_plugin_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->connection = $connection;
    $this->pluginManager = $node_revision_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousRevisions(int $nid, int $currently_deleted_revision_id): array {
    // @todo check if the method can be improved.
    // Getting the node storage.
    $node_storage = $this->entityTypeManager->getStorage('node');
    // Getting the node.
    $node = $node_storage->load($nid);
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
          $revisions_before[] = $revision->getTranslation($langcode);
        }
      }
    }

    return $revisions_before;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeExistsInQueue(int $nid): int {
    $query = $this->connection->select(DatabaseQueue::TABLE_NAME, 'q');
    $query->condition('name', 'node_revision_delete');
    $query->condition('data', serialize($nid));
    $query->condition('expire', 0);
    $query->fields('q', ['item_id']);

    $result = $query->execute()->fetchCol();

    return !empty($result) ? $result[0] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemFromQueue(int $item_id): void {
    $this->connection->delete(DatabaseQueue::TABLE_NAME)
      ->condition('item_id', $item_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function contentTypeHasEnabledPlugins(string $content_type_id): bool {
    $settings = $this->pluginManager->getSettingsNodeType($content_type_id);
    if (isset($settings['plugin'])) {
      foreach ($settings['plugin'] as $plugin_settings) {
        $status = (bool) ($plugin_settings['status'] ?? FALSE);
        if ($status) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
