<?php

namespace Drupal\node_revision_delete\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager;

/**
 * Delete revisions for a node.
 *
 * @QueueWorker(
 *   id = "node_revision_delete",
 *   title = @Translation("Node revision delete"),
 *   cron = {"time" = 300}
 * )
 */
class NodeRevisionDelete extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The node revision delete plugin manager.
   *
   * @var \Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager
   */
  protected NodeRevisionDeletePluginManager $pluginManager;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pluginManager = $container->get('plugin.manager.node_revision_delete');
    $instance->logger = $container->get('logger.factory');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $node = $this->entityTypeManager->getStorage('node')->load($data);

    // When the node can no longer be found there is nothing to do here.
    if (!$node instanceof NodeInterface) {
      return;
    }

    // Get the active revision ID and create an array to store the latest
    // revision ID per language.
    $active_vid = $node->getRevisionId();
    $active_vids = [];

    // Get all revision IDs for the node.
    $vids = $this->entityTypeManager->getStorage('node')->revisionIds($node);

    // Sort the revisions from new to old.
    rsort($vids);

    // Loading revisions per language is not so straightforward in Drupal, we
    // have to load all revisions and check whether it has a translation for
    // each node language, but also whether this is the one 'affected' for that
    // language, otherwise we would also get the default language revisions.
    // This is similar how core shows revisions per language.
    // @see \Drupal\node\Controller\NodeController.
    $revisions_per_language = [];
    foreach ($vids as $vid) {
      /** @var \Drupal\node\NodeInterface $revision */
      $revision = $this->entityTypeManager->getStorage('node')->loadRevision($vid);

      // We have to track revisions per language, otherwise unexpected behavior
      // and even loss of data might occur. Try to find the revision language
      // by checking which translation was affected.
      // See https://www.drupal.org/project/node_revision_delete/issues/3118464
      foreach ($revision->getTranslationLanguages() as $langcode => $language) {
        if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $revision = $revision->getTranslation($langcode);
          break;
        }
      }

      // Set the active revision ID for the language.
      if (($revision->isDefaultRevision() && $revision->isLatestTranslationAffectedRevision()) || ($revision->wasDefaultRevision() && $revision->isLatestTranslationAffectedRevision())) {
        $active_vids[$revision->language()->getId()] = $vid;
      }
      $revisions_per_language[$revision->language()->getId()][] = $vid;
    }

    // Load all enabled plugins, and check all revisions per language.
    $plugin_definitions = $this->pluginManager->getDefinitions();
    $delete_revisions = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $settings = $this->pluginManager->getSettings($plugin_id, $node->bundle());
      if (empty($settings)) {
        continue;
      }
      // Only use enabled plugins.
      if ($settings['status']) {
        /** @var \Drupal\node_revision_delete\Plugin\NodeRevisionDeleteInterface $plugin */
        $plugin = $this->pluginManager->getPlugin($plugin_id, $settings['settings']);
        // Collect the revisions per language and plugin id which are allowed to
        // be deleted according to that plugin, this will allow us to have nice
        // reporting per plugin/language.
        foreach ($revisions_per_language as $langcode => $revisions) {
          $delete_revisions[$langcode][$plugin_id] = $plugin->checkRevisions($revisions, $active_vids[$langcode] ?? $active_vid);
        }
      }
    }

    // We allow deleting a revision if one of the plugins allow the revision to
    // be deleted, and none of the plugins want to keep it.
    $logger = $this->logger->get('node_revision_delete');
    $verbose_log = $this->configFactory->get('node_revision_delete.settings')->get('verbose_log');
    foreach ($vids as $revision_id) {
      // Never delete the latest revision for a language.
      if ($revision_id === $active_vid || in_array($revision_id, $active_vids, TRUE)) {
        continue;
      }

      // Check the opinions of the enabled plugins.
      $can_delete = FALSE;
      $responsible_plugins = [];
      foreach ($delete_revisions as $langcode => $plugins) {
        foreach ($plugins as $plugin_id => $revision_statuses) {
          // Check if the langcode / plugin has an opinion on the revision.
          if (!isset($revision_statuses[$revision_id])) {
            continue;
          }

          // Never delete a revision if one of the plugins returns FALSE.
          if (FALSE === $revision_statuses[$revision_id]) {
            continue 3;
          }

          // Mark the revision for deletion.
          if (TRUE === $revision_statuses[$revision_id]) {
            $can_delete = TRUE;
            $responsible_plugins[] = $plugin_id;
          }
        }
      }

      // The revision is marked for deletion and none of the plugins stopped it,
      // so we can now remove it safely.
      if ($can_delete) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($revision_id);
        if ($verbose_log) {
          $logger->info('Deleted revision @revision_id for node @node_id. (responsible plugins: @responsible_plugins)', [
            '@revision_id' => $revision_id,
            '@node_id' => $node->id(),
            '@responsible_plugins' => implode(', ', $responsible_plugins),
          ]);
        }
      }
    }
  }

}
